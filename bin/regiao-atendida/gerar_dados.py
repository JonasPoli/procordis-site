#!/usr/bin/env python3
"""
Gera os dados da página "Região atendida" (/regiao-atendida) e o mapa SVG da seção da home.

Entradas
  - docs/regiao-atendida/fonte-mapa-drs3.json: dados do mapa "Mapa DRS III Procordis"
    (municípios do DRS III, unidades do CNES, áreas estimadas, malha dos demais municípios de SP,
    rodovias e manchas urbanas).
  - API de agregados do IBGE (baixada na hora; use --cache DIR para guardar/reaproveitar as respostas):
      4714 (Censo 2022: população, área, densidade), 9514 (Censo 2022: sexo e idade),
      9515 (Censo 2022: índice de envelhecimento e idade mediana), 1378 (Censo 2010: população)
      e 6579 (estimativa de população 2025).

Saídas
  - public/data/regiao-atendida/indicadores.json  (lido pelo site e pelo JavaScript da página)
  - public/data/regiao-atendida/drs3.json         (geometrias e unidades do DRS III)
  - public/data/regiao-atendida/sp.json           (malha simplificada dos demais municípios de SP)
  - templates/regiao_atendida/_mapa_svg.html.twig (mapa da home e do topo da página)

Uso: python3 bin/regiao-atendida/gerar_dados.py [--cache var/ibge]
"""
import argparse
import collections
import gzip
import json
import math
import os
import re
import urllib.request
from datetime import date

RAIZ = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
FONTE = os.path.join(RAIZ, 'docs', 'regiao-atendida', 'fonte-mapa-drs3.json')
SAIDA = os.path.join(RAIZ, 'public', 'data', 'regiao-atendida')
SVG = os.path.join(RAIZ, 'templates', 'regiao_atendida', '_mapa_svg.html.twig')
IBGE = 'https://servicodados.ibge.gov.br/api/v3/agregados'

# Cores das Regiões de Saúde: paleta categórica validada (claro / escuro), na ordem azul, laranja, verde.
REGIOES = {
    'central': {'nome': 'Região de Saúde Central', 'curto': 'Central', 'cor': '#2a78d6', 'corEscuro': '#3987e5'},
    'coracao': {'nome': 'Região de Saúde Coração', 'curto': 'Coração', 'cor': '#eb6834', 'corEscuro': '#d95926'},
    'noroeste': {'nome': 'Região de Saúde Noroeste', 'curto': 'Noroeste', 'cor': '#1baf7a', 'corEscuro': '#199e70'},
}

# Cidades de referência no mapa do estado (centro urbano aproximado).
CIDADES_REFERENCIA = [
    ('São Paulo', -23.5505, -46.6333), ('Campinas', -22.9056, -47.0608), ('Ribeirão Preto', -21.1775, -47.8103),
    ('Bauru', -22.3246, -49.0871), ('São José do Rio Preto', -20.8113, -49.3758), ('Sorocaba', -23.5015, -47.4526),
    ('Santos', -23.9608, -46.3336), ('Presidente Prudente', -22.1256, -51.3889), ('Marília', -22.2139, -49.9458),
    ('Franca', -20.5386, -47.4008), ('Piracicaba', -22.7253, -47.6492), ('Araçatuba', -21.2089, -50.4328),
]

# Acentuação dos nomes do CNES (a base aberta vem sem acentos) e abreviações expandidas.
ACENTOS = dict(p.split(':') for p in '''
Abrao:Abrão Aclimacao:Aclimação Adelia:Adélia Agua:Água Aguas:Águas Alianca:Aliança Alvares:Álvares
Ambulatorio:Ambulatório Amelia:Amélia America:América Americo:Américo Anesia:Anésia Antonio:Antônio
Anturios:Antúrios Area:Área Arsenio:Arsênio Assistencia:Assistência Associacao:Associação Atencao:Atenção
Atilio:Atílio Aurelio:Aurélio Bancarios:Bancários Barao:Barão Basilio:Basílio Bonifacio:Bonifácio
Brandao:Brandão Brasilia:Brasília Cambui:Cambuí Candido:Cândido Capitao:Capitão Cassia:Cássia Cassio:Cássio
Centenario:Centenário Comercio:Comércio Conceicao:Conceição Cristovao:Cristóvão Custodio:Custódio
Damasio:Damásio Diagnostico:Diagnóstico Emilio:Emílio Esperanca:Esperança Espaco:Espaço Eudoxia:Eudóxia
Expedicionario:Expedicionário Felicia:Felícia Felicio:Felício Flavio:Flávio Gestao:Gestão Goncalves:Gonçalves
Gracas:Graças Guimaraes:Guimarães Hermogenes:Hermógenes Hilario:Hilário Hortencia:Hortência
Hortencias:Hortências Ibate:Ibaté Icarai:Icaraí Indaia:Indaiá Independencia:Independência
Intervencao:Intervenção Ipes:Ipês Irmaos:Irmãos Italia:Itália Italo:Ítalo Itapolis:Itápolis Joao:João
Joquei:Jóquei Jordao:Jordão Jose:José Julio:Júlio Junior:Júnior Lazaro:Lázaro Leonia:Leônia
Lourenco:Lourenço Lucia:Lúcia Luis:Luís Matao:Matão Mauricio:Maurício Medica:Médica Mendonca:Mendonça
Milenio:Milênio Mourao:Mourão Negrao:Negrão Nevio:Névio Nucleo:Núcleo Octavio:Octávio
Odontologica:Odontológica Odontologico:Odontológico Olimpio:Olímpio Opice:Ópice Osorio:Osório Otavio:Otávio
Paraiso:Paraíso Patria:Pátria Petunias:Petúnias Redencao:Redenção Referencia:Referência Rincao:Rincão
Romulo:Rômulo Sao:São Sebastiao:Sebastião Servico:Serviço Simoes:Simões Sindromes:Síndromes Saude:Saúde
Silvania:Silvânia Silvio:Sílvio Uniao:União Valeria:Valéria Victorio:Victório Vitorio:Vitório
Voluntarios:Voluntários Viii:VIII Jd:Jardim Pq:Parque Sta:Santa Res:Residencial Av:Av. Prof:Prof. Dep:Dep.
'''.split())


def bonito(texto):
    if not texto:
        return texto
    return re.sub(r'[A-Za-zÀ-ú]+', lambda m: ACENTOS.get(m.group(0), m.group(0)), texto)


def endereco(texto):
    """'Rua X 216' -> 'Rua X, 216'."""
    texto = bonito(texto or '').strip()
    return re.sub(r'^(.*?[A-Za-zÀ-ú.])\s+(\d+[A-Za-z]?|S/?N)$', r'\1, \2', texto)


def baixar(url, cache, nome):
    caminho = os.path.join(cache, nome) if cache else None
    if caminho and os.path.exists(caminho):
        with open(caminho, encoding='utf-8') as f:
            return json.load(f)
    req = urllib.request.Request(url, headers={'User-Agent': 'procordis-site/regiao-atendida'})
    with urllib.request.urlopen(req, timeout=120) as r:
        corpo = r.read()
    if corpo[:2] == b'\x1f\x8b':  # a API do IBGE responde em gzip mesmo sem Accept-Encoding
        corpo = gzip.decompress(corpo)
    dados = json.loads(corpo.decode('utf-8'))
    if caminho:
        os.makedirs(cache, exist_ok=True)
        with open(caminho, 'w', encoding='utf-8') as f:
            json.dump(dados, f, ensure_ascii=False)
    return dados


def numero(v):
    try:
        return float(v)
    except (TypeError, ValueError):
        return 0.0  # '-' no SIDRA = zero


def series(resposta):
    """{localidade: {(variavel, categorias...): valor}}"""
    out = collections.defaultdict(dict)
    for v in resposta:
        for r in v['resultados']:
            chave = tuple(list(c['categoria'].keys())[0] for c in r['classificacoes'])
            for s in r['series']:
                out[s['localidade']['id']][(v['id'],) + chave] = numero(list(s['serie'].values())[0])
    return out


# ---------- geometria ----------

def dp(pontos, tol):
    """Douglas-Peucker iterativo."""
    if len(pontos) < 5:
        return pontos
    manter = [False] * len(pontos)
    manter[0] = manter[-1] = True
    pilha = [(0, len(pontos) - 1)]
    while pilha:
        a, b = pilha.pop()
        ax, ay = pontos[a]
        bx, by = pontos[b]
        dx, dy = bx - ax, by - ay
        n = math.hypot(dx, dy) or 1e-12
        maior, idx = 0.0, -1
        for i in range(a + 1, b):
            px, py = pontos[i]
            d = abs(dy * px - dx * py + bx * ay - by * ax) / n if (dx or dy) else math.hypot(px - ax, py - ay)
            if d > maior:
                maior, idx = d, i
        if maior > tol and idx > 0:
            manter[idx] = True
            pilha += [(a, idx), (idx, b)]
    return [p for p, k in zip(pontos, manter) if k]


def simplificar(geom, tol, casas):
    def anel(a):
        s = dp(a, tol)
        if len(s) < 4:
            s = a if len(a) <= 8 else dp(a, tol / 4)
        return [[round(x, casas), round(y, casas)] for x, y in s]

    if geom['type'] == 'Polygon':
        return {'type': 'Polygon', 'coordinates': [anel(r) for r in geom['coordinates']]}
    return {'type': 'MultiPolygon', 'coordinates': [[anel(r) for r in p] for p in geom['coordinates']]}


def aneis_externos(geom):
    if geom['type'] == 'Polygon':
        return [geom['coordinates'][0]]
    return [p[0] for p in geom['coordinates']]


def km(lat1, lon1, lat2, lon2):
    r = 6371.0
    p1, p2 = math.radians(lat1), math.radians(lat2)
    a = math.sin((p2 - p1) / 2) ** 2 + math.cos(p1) * math.cos(p2) * math.sin(math.radians(lon2 - lon1) / 2) ** 2
    return 2 * r * math.asin(math.sqrt(a))


# ---------- principal ----------

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--cache', help='pasta para guardar/reaproveitar as respostas do IBGE')
    args = ap.parse_args()

    with open(FONTE, encoding='utf-8') as f:
        F = json.load(f)

    munis = sorted(F['munis'], key=lambda m: m['nome'])
    ids = [m['id'] for m in munis]
    loc = f"N6[{','.join(ids)}]|N3[35]"
    idades = ['93070', '93084', '93085', '93086', '93087', '93088', '93089', '93090', '93091', '93092', '93093',
              '93094', '93095', '93096', '93097', '93098', '49108', '49109', '60040', '60041', '6653']
    faixas = ['0 a 4', '5 a 9', '10 a 14', '15 a 19', '20 a 24', '25 a 29', '30 a 34', '35 a 39', '40 a 44',
              '45 a 49', '50 a 54', '55 a 59', '60 a 64', '65 a 69', '70 a 74', '75 a 79', '80 ou mais']
    faixa_de = {c: min(i, 16) for i, c in enumerate(idades)}
    idosos = set(idades[12:])

    p22 = series(baixar(f'{IBGE}/4714/periodos/2022/variaveis/93|6318|614?localidades={loc}', args.cache, '4714.json'))
    env = series(baixar(f'{IBGE}/9515/periodos/2022/variaveis/10612|10613|8845?localidades={loc}', args.cache, '9515.json'))
    ida = series(baixar(f'{IBGE}/9514/periodos/2022/variaveis/93?localidades={loc}'
                        f"&classificacao=2[4,5]|287[{','.join(idades)}]|286[113635]", args.cache, '9514.json'))
    p10 = series(baixar(f'{IBGE}/1378/periodos/2010/variaveis/93?localidades={loc}', args.cache, '1378.json'))
    e25 = series(baixar(f'{IBGE}/6579/periodos/2025/variaveis/9324?localidades={loc}', args.cache, '6579.json'))

    def perfil(i):
        a = p22[i]
        piramide = {'homens': [0.0] * 17, 'mulheres': [0.0] * 17}
        pop60 = 0.0
        for chave, v in ida[i].items():
            sexo = 'homens' if chave[1] == '4' else 'mulheres'
            piramide[sexo][faixa_de[chave[2]]] += v
            if chave[2] in idosos:
                pop60 += v
        pop = a[('93',)]
        pop10 = list(p10[i].values())[0]
        return {
            'populacao': int(pop),
            'populacao2010': int(pop10),
            'estimativa2025': int(e25[i][('9324',)]),
            'area': round(a[('6318',)], 1),
            'densidade': round(a[('614',)], 1),
            'crescimento': round((pop / pop10 - 1) * 100, 1),
            'pop60': int(pop60),
            'pct60': round(pop60 / pop * 100, 1),
            'indiceEnvelhecimento': round(env[i][('10612',)], 1),
            'idadeMediana': int(env[i][('10613',)]),
            '_piramide': piramide,
        }

    unidades = [u for u in F['units'] if not u['procordis']]
    proc = next(u for u in F['units'] if u['procordis'])
    basicas = collections.Counter(u['cid'] for u in unidades if u['basica'])

    municipios = []
    for m in munis:
        d = perfil(m['id'])
        municipios.append({'id': m['id'], 'nome': m['nome'], 'regiao': m['reg'], 'km': m['km'],
                           'unidadesBasicas': basicas[m['id']], **d})

    def soma(lista):
        t = collections.Counter()
        pir = {'homens': [0.0] * 17, 'mulheres': [0.0] * 17}
        for d in lista:
            for k in ('populacao', 'populacao2010', 'estimativa2025', 'area', 'pop60', 'unidadesBasicas'):
                t[k] += d.get(k, 0)
            for s in pir:
                for j in range(17):
                    pir[s][j] += d['_piramide'][s][j]
        return {
            'municipios': len(lista),
            'populacao': int(t['populacao']),
            'populacao2010': int(t['populacao2010']),
            'estimativa2025': int(t['estimativa2025']),
            'area': round(t['area'], 1),
            'densidade': round(t['populacao'] / t['area'], 1),
            'crescimento': round((t['populacao'] / t['populacao2010'] - 1) * 100, 1),
            'pop60': int(t['pop60']),
            'pct60': round(t['pop60'] / t['populacao'] * 100, 1),
            'unidadesBasicas': int(t['unidadesBasicas']),
            '_piramide': pir,
        }

    total = soma(municipios)
    estado = perfil('35')
    estado.update(municipios=645)
    regioes = {}
    for k, r in REGIOES.items():
        lista = [d for d in municipios if d['regiao'] == k]
        regioes[k] = {**r, **soma(lista), 'lista': [d['nome'] for d in lista]}
        regioes[k].pop('_piramide')
        regioes[k]['pctPopulacao'] = round(regioes[k]['populacao'] / total['populacao'] * 100, 1)

    def pct_piramide(p, pop):
        return {s: [round(v / pop * 100, 2) for v in vals] for s, vals in p.items()}

    piramide = {
        'faixas': faixas,
        'regiao': pct_piramide(total.pop('_piramide'), total['populacao']),
        'estado': pct_piramide(estado.pop('_piramide'), estado['populacao']),
    }
    for d in municipios:
        d.pop('_piramide')

    # Unidades de origem listadas (Araraquara), numeradas pela distância até a Procordis.
    listadas = sorted([u for u in unidades if u['listada']], key=lambda u: (u['km'] if u['km'] is not None else 99, u['nome']))
    numero_de = {u['cnes']: i + 1 for i, u in enumerate(listadas)}

    def tipo_listada(u):
        return 'primaria' if re.match(r'^(CMSC|USF|UBS|PSF|Qualis|ESF)\b', u['nome'], re.I) else 'especializada'

    origem = [{
        'n': numero_de[u['cnes']], 'cnes': u['cnes'], 'nome': bonito(u['nome']), 'endereco': endereco(u['end']),
        'bairro': bonito(u['bairro']), 'lat': u['lat'], 'lon': u['lon'], 'km': u['km'],
        'estimada': u['precisao'] == 'estimada', 'tipo': tipo_listada(u),
    } for u in listadas]

    sem_coordenada = [{'nome': bonito(u['nome']), 'municipio': next(m['nome'] for m in munis if m['id'] == u['cid']),
                       'cnes': u['cnes']} for u in unidades if not u['lat'] or u['precisao'] == 'fora']

    capital = km(proc['lat'], proc['lon'], -23.5505, -46.6333)

    indicadores = {
        'atualizadoEm': date.today().isoformat(),
        'consultaCnes': '2026-11-04',
        'procordis': {'lat': proc['lat'], 'lon': proc['lon'], 'cnes': proc['cnes'],
                      'endereco': 'Av. Queiroz Filho, 685', 'cidade': 'Araraquara – SP'},
        'distanciaCapitalKm': round(capital),
        'regioes': regioes,
        'total': total,
        'estado': estado,
        'participacaoEstado': {'populacao': round(total['populacao'] / estado['populacao'] * 100, 1),
                               'area': round(total['area'] / estado['area'] * 100, 1),
                               'municipios': round(len(municipios) / 645 * 100, 1)},
        'municipios': municipios,
        'piramide': piramide,
        'unidadesOrigem': origem,
        'resumoOrigem': collections.Counter(o['tipo'] for o in origem),
        'semCoordenada': sem_coordenada,
        'unidadesMapeadas': len(unidades),
    }

    # ---------- geometrias do DRS III ----------
    def unidade_publica(u):
        return {'cnes': u['cnes'], 'cid': u['cid'], 'nome': bonito(u['nome']), 'end': endereco(u['end']),
                'bairro': bonito(u['bairro']), 'lat': u['lat'], 'lon': u['lon'], 'km': u['km'],
                'basica': u['basica'], 'estimada': u['precisao'] == 'estimada', 'cor': u['cor'],
                'n': numero_de.get(u['cnes'])}

    drs3 = {
        'procordis': {'lat': proc['lat'], 'lon': proc['lon']},
        'municipios': [{'id': m['id'], 'nome': m['nome'], 'regiao': m['reg'], 'c': m['c'], 'km': m['km'],
                        'g': m['g']} for m in munis],
        'unidades': [unidade_publica(u) for u in unidades if u['lat'] and u['precisao'] != 'fora'],
        'areas': F['areas'],
        'rodovias': F['roads'],
        'urbano': F['urban'],
    }
    sp = {
        'municipios': [{'n': b['n'], 'g': simplificar(b['g'], 0.004, 3), **({'c': b['c']} if b.get('near') else {})}
                       for b in F['bg']],
        'cidades': [{'n': n, 'c': [lat, lon]} for n, lat, lon in CIDADES_REFERENCIA],
    }

    os.makedirs(SAIDA, exist_ok=True)
    for nome, dados in (('indicadores.json', indicadores), ('drs3.json', drs3), ('sp.json', sp)):
        with open(os.path.join(SAIDA, nome), 'w', encoding='utf-8') as f:
            json.dump(dados, f, ensure_ascii=False, separators=(',', ':'))
        print(nome, os.path.getsize(os.path.join(SAIDA, nome)), 'bytes')

    gerar_svg(munis, proc, indicadores)


def gerar_svg(munis, proc, ind):
    """Mapa estático dos 24 municípios (home e topo da página). Cores por CSS (data-regiao)."""
    lat0 = math.radians(-21.75)
    largura = 600.0
    xs, ys = [], []
    for m in munis:
        for a in aneis_externos(m['g']):
            for lon, lat in a:
                xs.append(lon * math.cos(lat0))
                ys.append(-lat)
    minx, maxx, miny, maxy = min(xs), max(xs), min(ys), max(ys)
    pad = 14
    esc = (largura - 2 * pad) / (maxx - minx)
    altura = (maxy - miny) * esc + 2 * pad

    def xy(lon, lat):
        return ((lon * math.cos(lat0) - minx) * esc + pad, (-lat - miny) * esc + pad)

    def caminho(geom):
        partes = []
        for a in aneis_externos(simplificar(geom, 0.0025, 5)):
            pts = [xy(lon, lat) for lon, lat in a]
            partes.append('M' + 'L'.join(f'{x:.1f} {y:.1f}' for x, y in pts) + 'Z')
        return ''.join(partes)

    pop = {d['id']: d['populacao'] for d in ind['municipios']}
    px, py = xy(proc['lon'], proc['lat'])
    km_px = esc / (111.32 * math.cos(lat0))  # px por km na horizontal
    destaque = {'Araraquara', 'São Carlos', 'Matão', 'Ibitinga', 'Itápolis', 'Taquaritinga', 'Porto Ferreira',
                'Descalvado', 'Ibaté', 'Boa Esperança do Sul', 'Borborema', 'Ribeirão Bonito'}

    linhas = [
        '{# Gerado por bin/regiao-atendida/gerar_dados.py - não editar à mão. Variáveis: svg_id, svg_classe, svg_rotulos #}',
        f'<svg id="{{{{ svg_id|default(\'ra-mapa-svg\') }}}}" class="ra-svg {{{{ svg_classe|default(\'\') }}}}" '
        f'viewBox="0 0 {largura:.0f} {altura:.0f}" role="img" '
        'aria-label="Mapa dos 24 municípios da região atendida pela Procordis, coloridos pela Região de Saúde: '
        'Central, Coração e Noroeste do DRS III – Araraquara">',
        '<g class="ra-svg-aneis" aria-hidden="true">',
    ]
    for r in (25, 50, 75):
        linhas.append(f'<circle cx="{px:.1f}" cy="{py:.1f}" r="{r * km_px:.1f}"/>')
    linhas.append('</g><g class="ra-svg-municipios">')
    for m in munis:
        linhas.append(f'<path data-regiao="{m["reg"]}" d="{caminho(m["g"])}">'
                      f'<title>{m["nome"]} · {pop[m["id"]]:,} habitantes</title></path>'.replace(',', '.'))
    linhas.append('</g>{% if svg_rotulos ?? true %}<g class="ra-svg-rotulos" aria-hidden="true">')
    for m in munis:
        if m['nome'] in destaque and m['nome'] != 'Araraquara':
            x, y = xy(m['c'][1], m['c'][0])
            linhas.append(f'<text x="{x:.1f}" y="{y:.1f}">{m["nome"]}</text>')
    linhas.append('</g>{% endif %}')
    linhas.append(f'<g class="ra-svg-procordis" aria-hidden="true" transform="translate({px:.1f} {py:.1f})">'
                  '<circle class="ra-svg-pulso" r="7"/><circle class="ra-svg-pulso ra-svg-pulso-2" r="7"/>'
                  '<circle class="ra-svg-ponto" r="6.5"/>'
                  '<path class="ra-svg-coracao" d="M0 3.2C-1.2 2.2-3.2 0.9-3.2-0.8c0-1 0.8-1.8 1.7-1.8 0.7 0 1.2 0.4 1.5 0.9 0.3-0.5 0.8-0.9 1.5-0.9 0.9 0 1.7 0.8 1.7 1.8C3.2 0.9 1.2 2.2 0 3.2Z"/>'
                  '{% if svg_rotulos ?? true %}<text class="ra-svg-rotulo-procordis" x="10" y="-9">Procordis · Araraquara</text>{% endif %}</g>')
    linhas.append('</svg>')
    with open(SVG, 'w', encoding='utf-8') as f:
        f.write('\n'.join(linhas) + '\n')
    print('_mapa_svg.html.twig', os.path.getsize(SVG), 'bytes')


if __name__ == '__main__':
    main()
