/*
 * Página "Região atendida" (/regiao-atendida).
 * Dados: indicadores embutidos na página (#ra-dados) + geometrias em /data/regiao-atendida/{drs3,sp}.json,
 * gerados por bin/regiao-atendida/gerar_dados.py.
 *
 * Mapas (Leaflet): estado de SP, mapa DRS III Procordis (unidades, regiões, camadas) e densidade populacional.
 * Gráficos em HTML: comparativo por indicador, ranking de densidade e pirâmide etária.
 * Cores das Regiões de Saúde: paleta categórica validada; no modo escuro cada cor troca pelo tom do fundo escuro.
 */
(function () {
    'use strict';

    const raiz = document.getElementById('ra-app');
    if (!raiz) {
        return;
    }

    const DADOS = JSON.parse(document.getElementById('ra-dados').textContent);
    const MUN = Object.fromEntries(DADOS.municipios.map((m) => [m.id, m]));
    const MUN_POR_NOME = Object.fromEntries(DADOS.municipios.map((m) => [m.nome, m]));
    const ESTADO = DADOS.estado;
    const TOTAL = DADOS.total;

    const COR_REGIAO = {
        central: ['#2a78d6', '#3987e5'],
        coracao: ['#eb6834', '#d95926'],
        noroeste: ['#1baf7a', '#199e70'],
    };
    /* Densidade (hab/km²): 5 classes, rampa sequencial azul (claro: claro→escuro; escuro: escuro→claro). */
    const DENS_LIMITES = [25, 50, 100, 200];
    const DENS_CORES = [
        ['#b7d3f6', '#6da7ec', '#2a78d6', '#1c5cab', '#0d366b'],
        ['#184f95', '#256abf', '#3987e5', '#6da7ec', '#b7d3f6'],
    ];
    const DENS_TEXTO_CLARO = [[false, false, true, true, true], [true, true, true, false, false]];

    const escuro = () => document.documentElement.classList.contains('dark');
    const modo = () => (escuro() ? 1 : 0);
    const corRegiao = (k) => COR_REGIAO[k][modo()];
    const varCss = (n) => `hsl(${getComputedStyle(document.documentElement).getPropertyValue(n).trim()})`;
    const fmt = (n, casas = 0) => Number(n).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas });
    const sinal = (n) => (n > 0 ? '+' : '') + fmt(n, 1);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const $ = (sel, el = document) => el.querySelector(sel);
    const $$ = (sel, el = document) => Array.from(el.querySelectorAll(sel));

    let T = tema();
    function tema() {
        const d = escuro();
        return {
            terra: d ? '#1f2b3b' : '#eef1ec',
            fora: d ? '#172130' : '#e7eaed',
            linha: d ? '#34435a' : '#c9d1d8',
            superficie: varCss('--card'),
            tinta: varCss('--foreground'),
            tinta2: varCss('--muted-foreground'),
            estrada: d ? '#c49a5a' : '#b58b4a',
            urbano: d ? '#3d3b33' : '#d7d2c4',
            procordis: d ? '#e5484d' : '#c0202c',
        };
    }

    const classeDensidade = (v) => DENS_LIMITES.filter((l) => v >= l).length;
    const corDensidade = (v) => DENS_CORES[modo()][classeDensidade(v)];

    const colecao = (itens) => ({
        type: 'FeatureCollection',
        features: itens.map(({ g, ...props }) => ({ type: 'Feature', properties: props, geometry: g })),
    });

    /* ---------------------------------------------------------------- popups */

    function linhasIndicadores(m) {
        return `<dl>
            <dt>População (2022)</dt><dd>${fmt(m.populacao)}</dd>
            <dt>Área</dt><dd>${fmt(m.area, 1)} km²</dd>
            <dt>Densidade</dt><dd>${fmt(m.densidade, 1)} hab/km²</dd>
            <dt>60 anos ou mais</dt><dd>${fmt(m.pct60, 1)}%</dd>
            <dt>Unidades básicas (CNES)</dt><dd>${m.unidadesBasicas}</dd>
            <dt>Distância da Procordis</dt><dd>${fmt(m.km, 1)} km</dd>
        </dl>`;
    }

    function popupMunicipio(id, extra = '') {
        const m = MUN[id];
        return `<div class="ra-pop"><p class="ra-pop-t">${esc(m.nome)}</p>
            <div class="ra-pop-k">${esc(DADOS.regioes[m.regiao].nome)} do DRS III</div>
            ${extra}${linhasIndicadores(m)}</div>`;
    }

    function tooltipMunicipio(id) {
        const m = MUN[id];
        return `<strong>${esc(m.nome)}</strong><br><span class="ra-pop-k">${esc(DADOS.regioes[m.regiao].curto)} · ${fmt(m.populacao)} hab.</span>`;
    }

    const linkMapa = (lat, lon) => `https://www.google.com/maps/search/?api=1&query=${lat},${lon}`;

    /* ---------------------------------------------------------------- interação dos mapas */

    /* A página rola por cima dos mapas: zoom pela roda só depois de clicar; no celular, um dedo rola a página. */
    function interacao(map, el) {
        const aviso = document.createElement('div');
        aviso.className = 'ra-aviso';
        el.appendChild(aviso);
        let tempo;
        const mostrar = (txt) => {
            aviso.textContent = txt;
            aviso.classList.add('ra-visivel');
            clearTimeout(tempo);
            tempo = setTimeout(() => aviso.classList.remove('ra-visivel'), 1500);
        };
        map.scrollWheelZoom.disable();
        el.addEventListener('wheel', () => {
            if (!map.scrollWheelZoom.enabled()) {
                mostrar('Clique no mapa para ampliar com a roda do mouse');
            }
        }, { passive: true });
        map.on('click focus', () => map.scrollWheelZoom.enable());
        el.addEventListener('mouseleave', () => map.scrollWheelZoom.disable());
        if (L.Browser.mobile) {
            el.addEventListener('touchmove', (e) => {
                if (e.touches.length === 1) {
                    mostrar('Use dois dedos para mover o mapa');
                }
            }, { passive: true });
        }
    }

    function novoMapa(el, opcoes = {}) {
        $$('.ra-carregando', el).forEach((c) => c.remove());
        const map = L.map(el, {
            zoomSnap: 0.25,
            zoomDelta: 0.5,
            dragging: !L.Browser.mobile,
            tap: !L.Browser.mobile,
            ...opcoes,
        });
        map.attributionControl.setPrefix(false);
        interacao(map, el);
        return map;
    }

    function iconeProcordis() {
        return L.divIcon({
            className: '',
            html: '<div class="ra-pino-procordis" aria-label="Procordis"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.3 3 4.5 6.7 4.5c2.1 0 3.6 1.1 4.4 2.4l.9 1.4.9-1.4c.8-1.3 2.3-2.4 4.4-2.4 3.7 0 5.8 3.8 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
        });
    }

    const POPUP_PROCORDIS = () => {
        const p = DADOS.procordis;
        return `<div class="ra-pop"><p class="ra-pop-t">Associação Procordis Araraquara</p>
            <div>${esc(p.endereco)}<br>${esc(p.cidade)}</div>
            <div class="ra-pop-m" style="margin-top:6px">CNES ${p.cnes}<br>${p.lat.toFixed(6)}, ${p.lon.toFixed(6)}</div>
            <div style="margin-top:6px"><a href="${linkMapa(p.lat, p.lon)}" target="_blank" rel="noopener">Como chegar (Google Maps)</a></div></div>`;
    };

    const temaAtualizadores = [];

    /* ---------------------------------------------------------------- 1. Estado de SP */

    function mapaEstado(sp, drs) {
        const el = document.getElementById('ra-mapa-estado');
        const map = novoMapa(el, { minZoom: 5, maxZoom: 11 });
        map.attributionControl.addAttribution('Limites municipais: IBGE');

        const fundo = L.geoJSON(colecao(sp.municipios), {
            style: () => ({ color: T.linha, weight: 0.5, fillColor: T.terra, fillOpacity: 1 }),
            onEachFeature: (f, l) => l.bindTooltip(esc(f.properties.n), { sticky: true, className: 'ra-tip', direction: 'top', offset: [0, -8] }),
        });
        // A vista precisa existir antes de desenhar vetores (o Leaflet recorta os polígonos pela vista).
        const limites = fundo.getBounds();
        map.fitBounds(limites, { padding: [6, 6] });
        map.setMaxBounds(limites.pad(0.25));
        fundo.addTo(map);

        const estilo = (p) => ({ color: T.superficie, weight: 0.8, fillColor: corRegiao(p.regiao), fillOpacity: 0.92 });
        const drsCamada = L.geoJSON(colecao(drs.municipios), {
            style: (f) => estilo(f.properties),
            onEachFeature: (f, l) => {
                l.bindTooltip(() => tooltipMunicipio(f.properties.id), { sticky: true, className: 'ra-tip', direction: 'top', offset: [0, -8] });
                l.bindPopup(() => popupMunicipio(f.properties.id));
                l.on('mouseover', () => l.setStyle({ weight: 2, color: T.tinta }));
                l.on('mouseout', () => l.setStyle(estilo(f.properties)));
            },
        }).addTo(map);

        const cidades = L.layerGroup().addTo(map);
        const aEsquerda = ['São José do Rio Preto', 'Presidente Prudente'];
        sp.cidades.forEach((c) => {
            L.circleMarker(c.c, { radius: 3, weight: 1.5, color: T.superficie, fillColor: T.tinta2, fillOpacity: 1, interactive: false }).addTo(cidades);
            const esquerda = aEsquerda.includes(c.n);
            L.tooltip({ permanent: true, direction: esquerda ? 'left' : 'right', offset: [esquerda ? -4 : 4, 0], className: 'ra-rotulo ra-fraco' })
                .setLatLng(c.c).setContent(c.n === 'São Paulo' ? 'São Paulo (capital)' : c.n).addTo(cidades);
        });
        L.marker([DADOS.procordis.lat, DADOS.procordis.lon], { icon: iconeProcordis(), zIndexOffset: 1000 })
            .bindPopup(POPUP_PROCORDIS).addTo(map);
        L.tooltip({ permanent: true, direction: 'top', offset: [0, -2], className: 'ra-rotulo ra-caixa' })
            .setLatLng([-21.25, -48.7]).setContent('DRS III<br><span class="ra-pop-k">Araraquara</span>').addTo(map);

        new ResizeObserver(() => map.invalidateSize()).observe(el);

        temaAtualizadores.push(() => {
            fundo.setStyle({ color: T.linha, fillColor: T.terra });
            drsCamada.setStyle((f) => estilo(f.properties));
            cidades.eachLayer((l) => l.setStyle && l.setStyle({ color: T.superficie, fillColor: T.tinta2 }));
        });
    }

    /* ---------------------------------------------------------------- 2. Mapa DRS III Procordis */

    let mapaDrs = null;
    let irParaUnidade = () => {};

    function mapaDrs3(sp, drs) {
        const el = document.getElementById('ra-mapa-drs3');
        const map = novoMapa(el, { minZoom: 6, maxZoom: 18 });
        mapaDrs = map;
        map.attributionControl.addAttribution('IBGE · CNES/DataSUS · Natural Earth');
        L.control.scale({ imperial: false, position: 'bottomright' }).addTo(map);

        ['fundo', 'urbano', 'municipio', 'area', 'estrada', 'anel', 'limite', 'pontos', 'procordis']
            .forEach((p, i) => { map.createPane('ra-' + p).style.zIndex = 300 + i * 10; });
        const ajustarMistura = () => { map.getPane('ra-area').style.mixBlendMode = escuro() ? 'normal' : 'multiply'; };
        ajustarMistura();

        // Mapa de ruas: OpenStreetMap (atribuição obrigatória). Tons neutralizados por CSS (.ra-ruas) para não
        // competir com as cores das regiões; no modo escuro o CSS inverte os tons.
        const ruas = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
            maxZoom: 19,
            className: 'ra-ruas',
        });

        const fundo = L.geoJSON(colecao(sp.municipios), {
            pane: 'ra-fundo', interactive: false,
            style: () => ({ color: T.linha, weight: 0.6, fillColor: T.fora, fillOpacity: 1 }),
        });
        const rotulosFundo = L.layerGroup();
        sp.municipios.filter((b) => b.c).forEach((b) => {
            L.tooltip({ permanent: true, direction: 'center', className: 'ra-rotulo ra-fraco', pane: 'ra-limite' })
                .setLatLng(b.c).setContent(esc(b.n)).addTo(rotulosFundo);
        });
        const urbano = L.geoJSON(colecao(drs.urbano.map((g) => ({ g }))), {
            pane: 'ra-urbano', interactive: false, style: () => ({ stroke: false, fillColor: T.urbano, fillOpacity: 0.75 }),
        });
        const estiloEstrada = (f) => ({ color: T.estrada, weight: f.properties.t === 'Major Highway' ? 2.4 : 1.5, opacity: 0.9 });
        const estradas = L.geoJSON(colecao(drs.rodovias), { pane: 'ra-estrada', interactive: false, style: estiloEstrada });

        /* Municípios */
        const regiaoLigada = { central: true, coracao: true, noroeste: true };
        const camadaMunicipios = L.layerGroup().addTo(map);
        const camadaLimites = L.layerGroup().addTo(map);
        const camadaRotulos = L.layerGroup().addTo(map);
        const municipios = {};
        const estiloMunicipio = (m) => ({ stroke: false, fillColor: corRegiao(m.regiao), fillOpacity: comRuas() ? 0.14 : 0.22 });
        drs.municipios.forEach((m) => {
            const dados = MUN[m.id];
            const poligono = L.geoJSON(m.g, { pane: 'ra-municipio', style: estiloMunicipio(m) })
                .bindPopup(() => popupMunicipio(m.id, `<div style="margin-top:6px">Origem dos encaminhamentos: <strong>SMS – ${esc(m.nome.toUpperCase())}</strong></div>`));
            const limite = L.geoJSON(m.g, { pane: 'ra-limite', interactive: false, style: { color: corRegiao(m.regiao), weight: 1.6, fill: false, opacity: 0.95 } });
            const grande = m.nome === 'Araraquara' || m.nome === 'São Carlos';
            const rotulo = L.tooltip({ permanent: true, direction: 'center', className: 'ra-rotulo' + (grande ? ' ra-grande' : ''), pane: 'ra-procordis' })
                .setLatLng(m.c).setContent(esc(m.nome));
            municipios[m.id] = { m, dados, poligono, limite, rotulo };
        });

        /* Áreas estimadas (Voronoi recortado no município) */
        const unidadePorCnes = Object.fromEntries(drs.unidades.map((u) => [u.cnes, u]));
        const camadaAreas = L.layerGroup();
        const ARARAQUARA = MUN_POR_NOME.Araraquara.id;
        const areas = drs.areas.map((a) => {
            const us = a.cnes.map((c) => unidadePorCnes[c]).filter(Boolean);
            const principal = us.find((u) => u.n) || us[0];
            const camada = L.geoJSON(a.g, {
                pane: 'ra-area',
                style: { color: principal.cor, weight: principal.n ? 1.2 : 0.6, opacity: 0.7, fillColor: principal.cor, fillOpacity: principal.n ? 0.32 : 0.16 },
            }).bindPopup(() => `<div class="ra-pop"><p class="ra-pop-t">Área estimada</p>${us.map((u) => `<div>${esc(u.nome)}</div>`).join('')}
                <div class="ra-pop-k" style="margin-top:6px">Parte do município mais próxima desta unidade do que de qualquer outra UBS/USF. Aproximação: não é o território oficial.</div></div>`);
            return { a, principal, camada };
        });

        /* Unidades */
        const camadaOrigem = L.layerGroup();
        const camadaOutrasAraraquara = L.layerGroup();
        const camadaOutras = L.layerGroup();
        const marcadores = {};
        function popupUnidade(u) {
            const m = MUN[u.cid];
            return `<div class="ra-pop"><p class="ra-pop-t">${u.n ? `<span class="ra-num" style="width:20px;height:20px;font-size:10px;margin-right:6px;vertical-align:-4px">${u.n}</span>` : ''}${esc(u.nome)}</p>
                <div>${esc(u.end)}${u.bairro ? ' – ' + esc(u.bairro) : ''}<br>${esc(m.nome)} · ${esc(DADOS.regioes[m.regiao].curto)}</div>
                ${u.n ? '<div class="ra-pop-k" style="margin-top:4px">Unidade de origem dos encaminhamentos à Procordis</div>' : ''}
                ${u.estimada ? '<div class="ra-alerta" style="margin-top:4px">Posição estimada pelo endereço (± 100 m)</div>' : ''}
                <div class="ra-pop-m" style="margin-top:6px">CNES ${u.cnes}<br>${u.lat.toFixed(6)}, ${u.lon.toFixed(6)}</div>
                ${u.km != null ? `<div style="margin-top:4px"><strong>${fmt(u.km, 1)} km</strong> da Procordis (linha reta)</div>` : ''}
                <div style="margin-top:6px"><a href="${linkMapa(u.lat, u.lon)}" target="_blank" rel="noopener">Abrir no Google Maps</a></div></div>`;
        }
        const estiloOutra = (u) => (u.cid === ARARAQUARA
            ? { radius: 4.5, color: corRegiao('central'), weight: 2, fillColor: T.superficie, fillOpacity: 1 }
            : { radius: 4.5, color: T.superficie, weight: 1.2, fillColor: corRegiao(MUN[u.cid].regiao), fillOpacity: 1 });
        drs.unidades.forEach((u) => {
            let mk;
            if (u.n) {
                const tipo = DADOS.unidadesOrigem.find((o) => o.cnes === u.cnes)?.tipo || 'primaria';
                mk = L.marker([u.lat, u.lon], {
                    pane: 'ra-pontos',
                    icon: L.divIcon({ className: '', html: `<span class="ra-pino-num" data-tipo="${tipo}">${u.n}</span>`, iconSize: [22, 22], iconAnchor: [11, 11] }),
                    title: u.nome,
                    riseOnHover: true,
                });
                mk.bindTooltip(esc(u.nome), { direction: 'right', offset: [10, 0], className: 'ra-tip' });
                camadaOrigem.addLayer(mk);
            } else {
                mk = L.circleMarker([u.lat, u.lon], { pane: 'ra-pontos', ...estiloOutra(u) });
                mk.bindTooltip(esc(u.nome), { direction: 'top', offset: [0, -4], className: 'ra-tip' });
                (u.cid === ARARAQUARA ? camadaOutrasAraraquara : camadaOutras).addLayer(mk);
            }
            mk.bindPopup(() => popupUnidade(u));
            marcadores[u.cnes] = { mk, u };
        });

        /* Procordis e anéis de distância */
        const P = [DADOS.procordis.lat, DADOS.procordis.lon];
        const procordis = L.marker(P, { pane: 'ra-procordis', icon: iconeProcordis(), zIndexOffset: 1000 })
            .bindPopup(POPUP_PROCORDIS)
            .bindTooltip('Procordis', { direction: 'top', offset: [0, -16], className: 'ra-rotulo ra-grande ra-caixa', permanent: true })
            .addTo(map);
        const aneis = L.layerGroup();
        function desenharAneis() {
            aneis.clearLayers();
            [5, 10, 25, 50].forEach((k) => {
                L.circle(P, { radius: k * 1000, pane: 'ra-anel', color: T.procordis, weight: 1, opacity: 0.6, dashArray: '4 5', fill: false, interactive: false }).addTo(aneis);
                L.tooltip({ permanent: true, direction: 'center', className: 'ra-rotulo ra-fraco', pane: 'ra-anel' })
                    .setLatLng([P[0] + k / 111.32, P[1]]).setContent(k + ' km').addTo(aneis);
            });
        }
        desenharAneis();

        /* Controles */
        const marcado = (nome) => $(`[data-camada="${nome}"]`).checked;
        function comRuas() { return $('[data-camada="ruas"]').checked; }

        function atualizar() {
            const ruasLigadas = comRuas();
            [[ruas, ruasLigadas], [fundo, !ruasLigadas], [urbano, !ruasLigadas], [estradas, !ruasLigadas]]
                .forEach(([camada, ligar]) => (ligar ? camada.addTo(map) : map.removeLayer(camada)));
            Object.values(municipios).forEach(({ m, poligono, limite, rotulo }) => {
                const ligar = regiaoLigada[m.regiao];
                [[camadaMunicipios, poligono], [camadaLimites, limite], [camadaRotulos, rotulo]]
                    .forEach(([g, l]) => (ligar ? g.addLayer(l) : g.removeLayer(l)));
                poligono.setStyle(estiloMunicipio(m));
            });
            areas.forEach(({ a, principal, camada }) => {
                const ligar = marcado('areas') && regiaoLigada[MUN[a.cid].regiao] && (principal.cid === ARARAQUARA || marcado('outras'));
                ligar ? camadaAreas.addLayer(camada) : camadaAreas.removeLayer(camada);
            });
            [['areas', camadaAreas], ['origem', camadaOrigem], ['outrasAraraquara', camadaOutrasAraraquara], ['aneis', aneis]]
                .forEach(([nome, camada]) => {
                    const ligar = nome === 'areas' || marcado(nome);
                    ligar && (nome !== 'origem' && nome !== 'outrasAraraquara' || regiaoLigada.central) ? camada.addTo(map) : map.removeLayer(camada);
                });
            camadaOutras.clearLayers();
            if (marcado('outras')) {
                Object.values(marcadores).forEach(({ mk, u }) => {
                    if (!u.n && u.cid !== ARARAQUARA && regiaoLigada[MUN[u.cid].regiao]) {
                        camadaOutras.addLayer(mk);
                    }
                });
            }
            camadaOutras.addTo(map);
            rotulosPorZoom();
        }

        function rotulosPorZoom() {
            const z = map.getZoom();
            const ruasLigadas = comRuas();
            // Com o mapa de ruas, a partir do zoom 11 valem os nomes de bairros e ruas do próprio mapa.
            (ruasLigadas && z >= 11) ? map.removeLayer(camadaRotulos) : camadaRotulos.addTo(map);
            (!ruasLigadas && z >= 9) ? rotulosFundo.addTo(map) : map.removeLayer(rotulosFundo);
        }
        map.on('zoomend', rotulosPorZoom);

        $$('[data-camada]').forEach((c) => c.addEventListener('change', atualizar));
        $$('[data-filtro-regiao]').forEach((c) => c.addEventListener('change', () => {
            regiaoLigada[c.dataset.filtroRegiao] = c.checked;
            atualizar();
        }));
        $$('.ra-municipio').forEach((b) => b.addEventListener('click', () => {
            const it = municipios[MUN_POR_NOME[b.dataset.municipio].id];
            map.flyToBounds(it.poligono.getBounds(), { padding: [30, 30], duration: 0.6 });
            setTimeout(() => it.poligono.openPopup(it.m.c), 650);
        }));

        const limitesDrs = L.featureGroup(Object.values(municipios).map((x) => x.poligono)).getBounds();
        const limitesAraraquara = municipios[ARARAQUARA].poligono.getBounds();
        const limitesCidade = L.latLngBounds([[-21.835, -48.222], [-21.722, -48.118]]);
        const limitesEstado = fundo.getBounds().extend(limitesDrs);
        const vistas = {
            drs: () => map.flyToBounds(limitesDrs, { padding: [20, 20], duration: 0.6 }),
            municipio: () => map.flyToBounds(limitesAraraquara, { padding: [20, 20], duration: 0.6 }),
            cidade: () => map.flyToBounds(limitesCidade, { duration: 0.6 }),
            procordis: () => { map.flyTo(P, 15, { duration: 0.6 }); setTimeout(() => procordis.openPopup(), 650); },
            estado: () => map.flyToBounds(limitesEstado, { duration: 0.6 }),
        };
        $$('[data-vista]').forEach((b) => b.addEventListener('click', () => vistas[b.dataset.vista]()));

        irParaUnidade = (cnes) => {
            const alvo = marcadores[cnes];
            if (!alvo) {
                return;
            }
            if (!marcado('origem') && alvo.u.n) {
                $('[data-camada="origem"]').checked = true;
                atualizar();
            }
            map.flyTo([alvo.u.lat, alvo.u.lon], 16, { duration: 0.6 });
            setTimeout(() => alvo.mk.openPopup(), 650);
        };
        $$('#ra-lista-unidades button').forEach((b) => b.addEventListener('click', () => irParaUnidade(Number(b.dataset.cnes))));

        const coordenadas = document.getElementById('ra-coordenadas');
        map.on('mousemove', (e) => { coordenadas.textContent = e.latlng.lat.toFixed(5) + ', ' + e.latlng.lng.toFixed(5); });

        map.fitBounds(limitesDrs, { padding: [16, 16] });
        atualizar();
        new ResizeObserver(() => map.invalidateSize()).observe(el);

        temaAtualizadores.push(() => {
            ajustarMistura();
            fundo.setStyle({ color: T.linha, fillColor: T.fora });
            urbano.setStyle({ fillColor: T.urbano });
            estradas.setStyle(estiloEstrada);
            Object.values(municipios).forEach(({ m, poligono, limite }) => {
                poligono.setStyle(estiloMunicipio(m));
                limite.setStyle({ color: corRegiao(m.regiao) });
            });
            Object.values(marcadores).forEach(({ mk, u }) => mk.setStyle && mk.setStyle(estiloOutra(u)));
            desenharAneis();
        });
    }

    /* "Ver no mapa" do diretório: sobe até o mapa DRS III e abre a unidade. */
    $$('[data-ver-no-mapa]').forEach((b) => b.addEventListener('click', () => {
        document.getElementById('mapa-drs3').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => irParaUnidade(Number(b.dataset.verNoMapa)), 500);
    }));

    /* ---------------------------------------------------------------- 3. Densidade */

    function mapaDensidade(sp, drs) {
        const el = document.getElementById('ra-mapa-densidade');
        const map = novoMapa(el, { minZoom: 7, maxZoom: 12 });
        map.attributionControl.addAttribution('IBGE – Censo 2022');

        const fundo = L.geoJSON(colecao(sp.municipios), {
            interactive: false,
            style: () => ({ color: T.linha, weight: 0.6, fillColor: T.fora, fillOpacity: 1 }),
        });
        const estilo = (id, ativo = false) => ({
            color: ativo ? T.tinta : T.superficie,
            weight: ativo ? 2.5 : 1,
            fillColor: corDensidade(MUN[id].densidade),
            fillOpacity: 1,
        });
        const poligonos = {};
        const camada = L.geoJSON(colecao(drs.municipios), {
            style: (f) => estilo(f.properties.id),
            onEachFeature: (f, l) => {
                const id = f.properties.id;
                poligonos[id] = l;
                l.bindPopup(() => popupMunicipio(id));
                l.bindTooltip(() => `<strong>${esc(MUN[id].nome)}</strong><br>${fmt(MUN[id].densidade, 1)} hab/km² · ${fmt(MUN[id].populacao)} hab.`,
                    { sticky: true, className: 'ra-tip', direction: 'top', offset: [0, -8] });
                l.on('mouseover', () => destacar(id, true));
                l.on('mouseout', () => destacar(id, false));
            },
        });
        // A vista precisa existir antes de desenhar vetores (o Leaflet recorta os polígonos pela vista).
        map.fitBounds(camada.getBounds(), { padding: [12, 12] });
        fundo.addTo(map);
        camada.addTo(map);

        const rotulos = L.layerGroup().addTo(map);
        function desenharRotulos() {
            rotulos.clearLayers();
            drs.municipios.forEach((m) => {
                const d = MUN[m.id];
                const claro = DENS_TEXTO_CLARO[modo()][classeDensidade(d.densidade)];
                const t = L.tooltip({ permanent: true, direction: 'center', className: 'ra-rotulo-densidade ' + (claro ? 'ra-rotulo-claro' : 'ra-rotulo-escuro') })
                    .setLatLng(m.c).setContent(`${esc(m.nome)}<b>${fmt(d.densidade, 0)}</b>`);
                t.populacao = d.populacao;
                rotulos.addLayer(t);
            });
            posicionarRotulos();
        }
        /*
         * Evita rótulos sobrepostos: do município mais populoso para o menor, tenta a posição central e
         * pequenos deslocamentos; se nenhum couber, o rótulo some (o valor continua no tooltip e no ranking).
         */
        function posicionarRotulos() {
            const tentativas = [[0, 0], [0, -10], [0, 10], [-14, 0], [14, 0], [0, -18], [0, 18], [-16, -10], [16, 10], [16, -10], [-16, 10]];
            const ocupados = [];
            rotulos.getLayers().sort((a, b) => b.populacao - a.populacao).forEach((t) => {
                const el = t.getElement();
                if (!el) {
                    return;
                }
                el.style.visibility = '';
                const cabe = tentativas.some(([dx, dy]) => {
                    t.options.offset = [dx, dy];
                    t.update();
                    const r = el.getBoundingClientRect();
                    const livre = !ocupados.some((o) => r.left < o.right && r.right > o.left && r.top < o.bottom && r.bottom > o.top);
                    livre && ocupados.push(r);
                    return livre;
                });
                if (!cabe) {
                    el.style.visibility = 'hidden';
                }
            });
        }
        desenharRotulos();
        // Em zoom baixo os nomes dos municípios pequenos se sobrepõem: rótulos só a partir do zoom 8.
        const rotulosPorZoom = () => {
            if (map.getZoom() >= 8) {
                rotulos.addTo(map);
                posicionarRotulos();
            } else {
                map.removeLayer(rotulos);
            }
        };
        map.on('zoomend', rotulosPorZoom);
        map.on('resize', () => setTimeout(posicionarRotulos, 50));

        // Procordis só como ponto discreto: o pino grande esconderia o rótulo de Araraquara.
        L.circleMarker([DADOS.procordis.lat, DADOS.procordis.lon], { radius: 5, weight: 2, color: '#fff', fillColor: T.procordis, fillOpacity: 1 })
            .bindTooltip('Procordis', { direction: 'top', offset: [0, -6], className: 'ra-tip' })
            .bindPopup(POPUP_PROCORDIS).addTo(map);

        rotulosPorZoom();
        new ResizeObserver(() => map.invalidateSize()).observe(el);

        /* Ranking ao lado do mapa (ligado ao mapa pelo destaque) */
        const ranking = document.getElementById('ra-ranking-densidade');
        function destacar(id, ativo) {
            const l = poligonos[id];
            if (l) {
                l.setStyle(estilo(id, ativo));
                ativo && l.bringToFront();
            }
            const linha = ranking.querySelector(`[data-id="${id}"]`);
            linha && linha.classList.toggle('ra-ativa', ativo);
        }
        desenharBarras(ranking, {
            valor: (m) => m.densidade,
            texto: (m) => fmt(m.densidade, 1),
            cor: (m) => corDensidade(m.densidade),
            referencias: [{ valor: ESTADO.densidade, rotulo: 'SP ' + fmt(ESTADO.densidade, 0) }, { valor: TOTAL.densidade, rotulo: 'Região ' + fmt(TOTAL.densidade, 0) }],
            aoPassar: destacar,
            compacto: true,
        });

        /* Escala */
        function desenharEscala() {
            const nomes = ['menos de 25', '25 a 50', '50 a 100', '100 a 200', '200 ou mais'];
            const contagem = [0, 0, 0, 0, 0];
            DADOS.municipios.forEach((m) => contagem[classeDensidade(m.densidade)]++);
            document.getElementById('ra-escala-densidade').innerHTML = '<strong>hab/km²</strong>' + nomes
                .map((n, i) => `<span><i style="background:${DENS_CORES[modo()][i]}"></i>${n} <small>(${contagem[i]})</small></span>`).join('');
        }
        desenharEscala();

        temaAtualizadores.push(() => {
            fundo.setStyle({ color: T.linha, fillColor: T.fora });
            camada.setStyle((f) => estilo(f.properties.id));
            desenharRotulos();
            rotulosPorZoom();
            desenharEscala();
            $$('.ra-barra', ranking).forEach((b) => { $('.ra-barra-marca', b).style.background = corDensidade(MUN[b.dataset.id].densidade); });
        });
    }

    /* ---------------------------------------------------------------- barras horizontais */

    const tooltip = document.createElement('div');
    tooltip.className = 'ra-tooltip';
    tooltip.setAttribute('role', 'tooltip');
    raiz.appendChild(tooltip);

    function mostrarTooltip(m, atual, evento) {
        const linhas = [
            ['populacao', 'População 2022', fmt(m.populacao)],
            ['densidade', 'Densidade', fmt(m.densidade, 1) + ' hab/km²'],
            ['pct60', '60 anos ou mais', fmt(m.pct60, 1) + '%'],
            ['crescimento', 'Crescimento 2010–2022', sinal(m.crescimento) + '%'],
            ['km', 'Distância da Procordis', fmt(m.km, 1) + ' km'],
            ['unidadesBasicas', 'Unidades básicas', fmt(m.unidadesBasicas)],
        ];
        tooltip.innerHTML = `<div class="ra-tt-titulo" data-regiao="${m.regiao}"><span class="ra-quadrado"></span>${esc(m.nome)}</div>
            <div class="ra-pop-k" style="margin-bottom:6px">${esc(DADOS.regioes[m.regiao].nome)}</div>
            <dl>${linhas.map(([k, r, v]) => `<dt class="${k === atual ? 'ra-atual' : ''}">${r}</dt><dd class="${k === atual ? 'ra-atual' : ''}">${v}</dd>`).join('')}</dl>`;
        posicionarTooltip(evento);
        tooltip.classList.add('ra-visivel');
    }
    function posicionarTooltip(e) {
        const r = tooltip.getBoundingClientRect();
        let x = (e.clientX ?? 0) + 16;
        let y = (e.clientY ?? 0) + 16;
        if (e.clientX === undefined && e.target) {
            const alvo = e.target.getBoundingClientRect();
            x = alvo.left + alvo.width / 2;
            y = alvo.bottom + 6;
        }
        if (x + r.width > window.innerWidth - 8) x = Math.max(8, (e.clientX ?? x) - r.width - 16);
        if (y + r.height > window.innerHeight - 8) y = Math.max(8, (e.clientY ?? y) - r.height - 16);
        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
    }
    const esconderTooltip = () => tooltip.classList.remove('ra-visivel');
    window.addEventListener('scroll', esconderTooltip, { passive: true });

    /*
     * Uma linha por município. As linhas não são recriadas ao trocar o indicador: a ordem muda por CSS (order)
     * e as barras animam a largura.
     */
    function desenharBarras(el, cfg) {
        if (!el.children.length) {
            el.innerHTML = DADOS.municipios.map((m) => `
                <div class="ra-barra" role="listitem" tabindex="0" data-id="${m.id}" data-regiao="${m.regiao}">
                    <span class="ra-barra-nome">${esc(m.nome)}</span>
                    <span class="ra-barra-trilho"><span class="ra-barra-marca"></span><span class="ra-barra-valor"></span></span>
                </div>`).join('');
            $$('.ra-barra', el).forEach((linha) => {
                const m = MUN[linha.dataset.id];
                const entrar = (e) => { mostrarTooltip(m, el.dataset.indicador, e); el._cfg.aoPassar && el._cfg.aoPassar(m.id, true); };
                const sair = () => { esconderTooltip(); el._cfg.aoPassar && el._cfg.aoPassar(m.id, false); };
                linha.addEventListener('mouseenter', entrar);
                linha.addEventListener('mousemove', posicionarTooltip);
                linha.addEventListener('mouseleave', sair);
                linha.addEventListener('focus', entrar);
                linha.addEventListener('blur', sair);
            });
        }
        el._cfg = cfg;
        el.dataset.indicador = cfg.indicador || '';

        const valores = DADOS.municipios.map((m) => cfg.valor(m));
        const refs = cfg.referencias || [];
        const min = Math.min(0, ...valores);
        const max = Math.max(0, ...valores, ...refs.map((r) => r.valor));
        const folgaEsq = min < 0 ? 14 : 0;
        const util = 100 - folgaEsq - (cfg.compacto ? 16 : 13);
        const pos = (v) => folgaEsq + ((v - min) / (max - min || 1)) * util;
        const zero = pos(0);

        const ordem = [...DADOS.municipios].sort((a, b) => (cfg.crescente ? cfg.valor(a) - cfg.valor(b) : cfg.valor(b) - cfg.valor(a)));
        ordem.forEach((m, i) => {
            const linha = el.querySelector(`[data-id="${m.id}"]`);
            const v = cfg.valor(m);
            const marca = $('.ra-barra-marca', linha);
            const rotulo = $('.ra-barra-valor', linha);
            linha.style.order = i;
            marca.style.background = cfg.cor ? cfg.cor(m) : '';
            marca.classList.toggle('ra-negativa', v < 0);
            marca.style.left = (v < 0 ? pos(v) : zero) + '%';
            marca.style.width = Math.abs(pos(v) - zero) + '%';
            rotulo.textContent = cfg.texto(m);
            if (v < 0) {
                rotulo.style.left = `calc(${pos(v)}% - 6px)`;
                rotulo.style.transform = 'translate(-100%, -50%)';
            } else {
                rotulo.style.left = `calc(${pos(v)}% + 6px)`;
                rotulo.style.transform = 'translateY(-50%)';
            }
            linha.setAttribute('aria-label', `${m.nome}: ${cfg.texto(m)}`);
        });

        $$('.ra-referencia, .ra-zero', el).forEach((r) => r.remove());
        // Linhas de referência sobre a área das barras (coluna do trilho)
        const primeira = el.querySelector('.ra-barra-trilho');
        const deslocamento = primeira ? primeira.offsetLeft : 0;
        const largura = primeira ? primeira.offsetWidth : 0;
        const colocar = (classe, v, rotulo, i) => {
            const div = document.createElement('div');
            div.className = classe;
            div.style.left = `${deslocamento + (largura * pos(v)) / 100}px`;
            if (rotulo) {
                div.innerHTML = `<span style="top:${-18 - i * 0}px">${esc(rotulo)}</span>`;
            }
            el.appendChild(div);
        };
        if (min < 0) {
            colocar('ra-zero', 0);
        }
        refs.forEach((r, i) => colocar('ra-referencia', r.valor, r.rotulo, i));
        el.style.marginTop = refs.length ? '22px' : '';
    }

    /* ---------------------------------------------------------------- 4. Comparativo */

    function comparativo() {
        const el = document.getElementById('ra-comparativo');
        const descricao = document.getElementById('ra-indicador-descricao');
        const IND = {
            populacao: {
                valor: (m) => m.populacao, texto: (m) => fmt(m.populacao),
                descricao: `Habitantes em cada município no Censo 2022. Total da região: ${fmt(TOTAL.populacao)}.`,
            },
            densidade: {
                valor: (m) => m.densidade, texto: (m) => fmt(m.densidade, 1),
                referencias: [{ valor: ESTADO.densidade, rotulo: `Estado ${fmt(ESTADO.densidade, 1)}` }],
                descricao: `Habitantes por km² (Censo 2022). Média da região: ${fmt(TOTAL.densidade, 1)}.`,
            },
            pct60: {
                valor: (m) => m.pct60, texto: (m) => fmt(m.pct60, 1) + '%',
                referencias: [{ valor: ESTADO.pct60, rotulo: `Estado ${fmt(ESTADO.pct60, 1)}%` }],
                descricao: `Parcela da população com 60 anos ou mais (Censo 2022). Média da região: ${fmt(TOTAL.pct60, 1)}%.`,
            },
            crescimento: {
                valor: (m) => m.crescimento, texto: (m) => sinal(m.crescimento) + '%',
                referencias: [{ valor: ESTADO.crescimento, rotulo: `Estado ${sinal(ESTADO.crescimento)}%` }],
                descricao: `Variação da população entre os Censos de 2010 e 2022. Região: ${sinal(TOTAL.crescimento)}%.`,
            },
            km: {
                valor: (m) => m.km, texto: (m) => fmt(m.km, 1) + ' km', crescente: true,
                descricao: 'Distância em linha reta da Procordis até o centro de cada município, do mais próximo ao mais distante.',
            },
            unidadesBasicas: {
                valor: (m) => m.unidadesBasicas, texto: (m) => fmt(m.unidadesBasicas),
                descricao: `Postos e centros de saúde/UBS cadastrados no CNES (consulta em ${DADOS.consultaCnes.split('-').reverse().join('/')}). Total: ${TOTAL.unidadesBasicas}.`,
            },
        };
        const abas = $$('#ra-abas-indicador [data-indicador]');
        function mostrar(chave) {
            abas.forEach((a) => a.setAttribute('aria-selected', String(a.dataset.indicador === chave)));
            descricao.textContent = IND[chave].descricao;
            desenharBarras(el, { ...IND[chave], indicador: chave, cor: (m) => corRegiao(m.regiao) });
        }
        abas.forEach((a) => a.addEventListener('click', () => mostrar(a.dataset.indicador)));
        mostrar('populacao');
        temaAtualizadores.push(() => mostrar($('#ra-abas-indicador [aria-selected="true"]').dataset.indicador));
        window.addEventListener('resize', debounce(() => mostrar($('#ra-abas-indicador [aria-selected="true"]').dataset.indicador), 150));
    }

    /* ---------------------------------------------------------------- 5. Pirâmide etária */

    function piramide() {
        const el = document.getElementById('ra-piramide');
        const p = DADOS.piramide;
        const max = Math.max(...p.regiao.homens, ...p.regiao.mulheres, ...p.estado.homens, ...p.estado.mulheres) * 1.08;
        const w = (v) => (v / max) * 100;
        const linhas = p.faixas.map((faixa, i) => ({ faixa, i })).reverse();
        el.innerHTML = '<div class="ra-pir-cabecalho"><span>Homens</span><span></span><span>Mulheres</span></div>' + linhas.map(({ faixa, i }) => {
            const h = p.regiao.homens[i];
            const m = p.regiao.mulheres[i];
            const he = p.estado.homens[i];
            const me = p.estado.mulheres[i];
            return `<div class="ra-pir-linha ${i >= 12 ? 'ra-idosa' : ''}" title="${faixa} anos · Homens ${fmt(h, 2)}% (SP ${fmt(he, 2)}%) · Mulheres ${fmt(m, 2)}% (SP ${fmt(me, 2)}%)">
                <div class="ra-pir-lado ra-pir-esq">
                    <span class="ra-pir-barra" style="width:${w(h)}%"></span>
                    <span class="ra-pir-estado" style="right:${w(he)}%"></span>
                    <span class="ra-pir-valor" style="right:calc(${Math.max(w(h), w(he))}% + 6px)">${fmt(h, 1)}%</span>
                </div>
                <div class="ra-pir-faixa">${faixa}</div>
                <div class="ra-pir-lado ra-pir-dir">
                    <span class="ra-pir-barra" style="width:${w(m)}%"></span>
                    <span class="ra-pir-estado" style="left:${w(me)}%"></span>
                    <span class="ra-pir-valor" style="left:calc(${Math.max(w(m), w(me))}% + 6px)">${fmt(m, 1)}%</span>
                </div>
            </div>`;
        }).join('');
    }

    /* ---------------------------------------------------------------- 6. Tabela */

    function tabela() {
        const t = document.getElementById('ra-tabela');
        const corpo = t.tBodies[0];
        $$('thead th', t).forEach((th, col) => th.addEventListener('click', () => {
            const numero = th.dataset.ordenar === 'numero';
            const atual = th.getAttribute('aria-sort');
            const dir = atual === 'descending' ? 'ascending' : atual === 'ascending' ? 'descending' : (numero ? 'descending' : 'ascending');
            $$('thead th', t).forEach((o) => o.removeAttribute('aria-sort'));
            th.setAttribute('aria-sort', dir);
            const valor = (tr) => tr.children[col].dataset.valor;
            const linhas = Array.from(corpo.rows).sort((a, b) => {
                const r = numero ? Number(valor(a)) - Number(valor(b)) : valor(a).localeCompare(valor(b), 'pt-BR');
                return dir === 'ascending' ? r : -r;
            });
            linhas.forEach((tr) => corpo.appendChild(tr));
        }));
    }

    /* ---------------------------------------------------------------- início */

    function debounce(fn, ms) {
        let t;
        return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
    }

    piramide();
    tabela();
    comparativo();

    function falhaMapa(el) {
        $$('.ra-carregando', el).forEach((c) => c.remove());
        el.insertAdjacentHTML('beforeend', '<p style="position:absolute;inset:0;z-index:1002;display:grid;place-items:center;text-align:center;padding:1.5rem;background:hsl(var(--muted))" class="text-muted-foreground">Não foi possível carregar o mapa agora. Recarregue a página para tentar de novo.</p>');
    }
    const MAPAS = [['ra-mapa-estado', mapaEstado], ['ra-mapa-drs3', mapaDrs3], ['ra-mapa-densidade', mapaDensidade]];

    if (typeof L === 'undefined') {
        MAPAS.forEach(([id]) => falhaMapa(document.getElementById(id)));
    } else {
        Promise.all([
            fetch(raiz.dataset.sp).then((r) => { if (!r.ok) throw new Error(r.status); return r.json(); }),
            fetch(raiz.dataset.drs3).then((r) => { if (!r.ok) throw new Error(r.status); return r.json(); }),
        ]).then(([sp, drs]) => {
            // Cada mapa isolado: se um falhar, os outros continuam.
            MAPAS.forEach(([id, criar]) => {
                try {
                    criar(sp, drs);
                } catch (e) {
                    console.error(`Região atendida: falha no mapa ${id}`, e);
                    falhaMapa(document.getElementById(id));
                }
            });
        }).catch((e) => {
            console.error('Região atendida: falha ao carregar os dados dos mapas', e);
            MAPAS.forEach(([id]) => falhaMapa(document.getElementById(id)));
        });
    }

    /* Troca de tema pelo botão do site (classe "dark" no <html>) */
    let eraEscuro = escuro();
    new MutationObserver(() => {
        if (escuro() === eraEscuro) {
            return;
        }
        eraEscuro = escuro();
        T = tema();
        temaAtualizadores.forEach((fn) => fn());
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();
