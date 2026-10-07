/*
 * Página "Atendimentos realizados" (Portal da Transparência).
 * Dados: /transparencia/atendimentos/dados (proxy com cache da API pública do procordis-painel).
 *
 * Cores: paleta categórica validada (claro e escuro); cada tipo mantém a sua cor mesmo quando outros
 * são ocultados. O total usa a cor do texto. No modo escuro cada cor troca pelo tom próprio do fundo escuro.
 */
(function () {
    'use strict';

    const app = document.getElementById('atd-app');
    if (!app) {
        return;
    }

    /* Tom do modo escuro para cada cor da paleta (cores fora da lista são usadas como estão). */
    const TOM_ESCURO = {
        '#2a78d6': '#3987e5', '#eb6834': '#d95926', '#1baf7a': '#199e70', '#eda100': '#c98500',
        '#e87ba4': '#d55181', '#008300': '#008300', '#4a3aa7': '#9085e9', '#e34948': '#e66767',
    };
    const PERIODOS = {
        dia: [['30', 'Últimos 30 dias'], ['90', 'Últimos 90 dias'], ['365', 'Último ano']],
        mes: [['12', 'Últimos 12 meses'], ['36', 'Últimos 3 anos'], ['tudo', 'Todo o histórico']],
        ano: [['tudo', 'Todo o histórico']],
    };
    const PADRAO = { dia: '90', mes: '36', ano: 'tudo' };
    const NOME_PERIODO = { dia: 'dia', mes: 'mês', ano: 'ano' };

    const el = {
        canvas: document.getElementById('atd-grafico'),
        carregando: document.getElementById('atd-carregando'),
        erro: document.getElementById('atd-erro'),
        legenda: document.getElementById('atd-legenda'),
        periodo: document.getElementById('atd-periodo'),
        multiplos: document.getElementById('atd-multiplos'),
        cabecalho: document.getElementById('atd-tabela-cabecalho'),
        corpo: document.getElementById('atd-tabela-corpo'),
        atualizado: document.getElementById('atd-atualizado'),
        subtitulo: document.getElementById('atd-subtitulo'),
    };

    const estado = { agrupamento: 'mes', periodo: PADRAO.mes, ocultos: new Set(), dados: null, graficos: [] };
    const fmt = (n) => Number(n).toLocaleString('pt-BR');
    const escuro = () => document.documentElement.classList.contains('dark');
    const cor = (hex) => (escuro() ? TOM_ESCURO[(hex || '').toLowerCase()] || hex : hex);
    const css = (nome) => `hsl(${getComputedStyle(document.documentElement).getPropertyValue(nome).trim()})`;

    function tema() {
        return {
            tinta: css('--foreground'),
            tinta2: css('--muted-foreground'),
            grade: escuro() ? 'rgba(148,163,184,.14)' : 'rgba(100,116,139,.14)',
            superficie: css('--card'),
        };
    }

    function alfa(hex, a) {
        const h = hex.replace('#', '');
        const [r, g, b] = [0, 2, 4].map((i) => parseInt(h.substr(i, 2), 16));
        return `rgba(${r},${g},${b},${a})`;
    }

    /* Linha vertical que acompanha o cursor (crosshair). */
    const crosshair = {
        id: 'atdCrosshair',
        afterDatasetsDraw(chart) {
            const ativo = chart.tooltip && chart.tooltip.getActiveElements();
            if (!ativo || !ativo.length) {
                return;
            }
            const x = ativo[0].element.x;
            const { top, bottom } = chart.chartArea;
            const ctx = chart.ctx;
            ctx.save();
            ctx.strokeStyle = tema().tinta2;
            ctx.globalAlpha = 0.35;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(x, top);
            ctx.lineTo(x, bottom);
            ctx.stroke();
            ctx.restore();
        },
    };

    function intervalo() {
        const p = estado.periodo;
        if (p === 'tudo') {
            return {};
        }
        const base = app.dataset.ultimaData ? new Date(app.dataset.ultimaData + 'T12:00:00') : new Date(Date.now() - 86400000);
        const de = new Date(base);
        if (estado.agrupamento === 'dia') {
            de.setDate(de.getDate() - (parseInt(p, 10) - 1));
        } else {
            de.setMonth(de.getMonth() - (parseInt(p, 10) - 1), 1);
        }
        const iso = (d) => d.toISOString().slice(0, 10);
        return { de: iso(de), ate: iso(base) };
    }

    function preencherPeriodos() {
        el.periodo.innerHTML = PERIODOS[estado.agrupamento].map(([v, l]) => `<option value="${v}">${l}</option>`).join('');
        el.periodo.value = estado.periodo;
        el.periodo.disabled = PERIODOS[estado.agrupamento].length < 2;
    }

    async function carregar() {
        el.carregando.classList.remove('hidden');
        el.erro.classList.add('hidden');
        el.erro.classList.remove('flex');
        const params = new URLSearchParams({ agrupamento: estado.agrupamento, ...intervalo() });
        try {
            const r = await fetch(`${app.dataset.endpoint}?${params}`, { headers: { Accept: 'application/json' } });
            if (!r.ok) {
                throw new Error('HTTP ' + r.status);
            }
            estado.dados = await r.json();
            desenhar();
        } catch (e) {
            el.erro.textContent = 'Não foi possível carregar os dados agora. Tente novamente em alguns minutos.';
            el.erro.classList.remove('hidden');
            el.erro.classList.add('flex');
        } finally {
            el.carregando.classList.add('hidden');
        }
    }

    function desenhar() {
        const d = estado.dados;
        if (!d || typeof Chart === 'undefined') {
            return;
        }
        estado.graficos.forEach((g) => g.destroy());
        estado.graficos = [];

        const series = d.series.filter((s) => s.total > 0);
        estado.parcial = ultimoParcial(d);
        legenda(series);
        principal(d, series);
        multiplos(d, series);
        tabela(d, series);
        el.subtitulo.textContent = `${fmt(d.total.total)} atendimentos realizados entre ${rotuloData(d.de)} e ${rotuloData(d.ate)}.`
            + (estado.parcial ? ` O último ${NOME_PERIODO[d.agrupamento]} ainda está em andamento (trecho tracejado).` : '');
        if (d.atualizadoEm) {
            el.atualizado.textContent = `Dados atualizados em ${new Date(d.atualizadoEm.replace(' ', 'T')).toLocaleDateString('pt-BR')}.`;
        }
        document.querySelectorAll('#atd-totais [data-cor]').forEach((p) => { p.style.background = cor(p.dataset.cor); });
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function legenda(series) {
        const itens = [{ slug: '__total', nome: 'Total', cor: null }, ...series];
        el.legenda.innerHTML = itens.map((s) => `
            <button type="button" class="atd-chip inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-border bg-card text-sm font-semibold text-foreground hover:border-primary transition"
                data-slug="${s.slug}" aria-pressed="${estado.ocultos.has(s.slug) ? 'false' : 'true'}">
                <span class="atd-chip-dot w-2.5 h-2.5 rounded-full" style="background:${s.cor ? cor(s.cor) : tema().tinta}"></span>${s.nome}
            </button>`).join('');
    }

    function principal(d, series) {
        const t = tema();
        const muitos = d.periodos.length > 45;
        const ctx = el.canvas.getContext('2d');
        const gradiente = ctx.createLinearGradient(0, 0, 0, el.canvas.clientHeight || 400);
        gradiente.addColorStop(0, escuro() ? 'rgba(248,250,252,.12)' : 'rgba(15,23,42,.08)');
        gradiente.addColorStop(1, 'rgba(0,0,0,0)');

        const datasets = [{
            label: 'Total', slug: '__total', data: d.total.valores, borderColor: t.tinta, backgroundColor: gradiente, fill: true,
            borderWidth: 2.5, pointRadius: muitos ? 0 : 3, pointHoverRadius: 6, pointBackgroundColor: t.tinta,
            pointBorderColor: t.superficie, pointBorderWidth: 2, tension: 0.3, hidden: estado.ocultos.has('__total'), order: 0,
            segment: tracejarUltimo(d.periodos.length),
        }].concat(series.map((s, i) => ({
            label: s.nome, slug: s.slug, data: s.valores, borderColor: cor(s.cor), backgroundColor: cor(s.cor),
            borderWidth: 2, pointRadius: muitos ? 0 : 2.5, pointHoverRadius: 5, pointBorderColor: t.superficie, pointBorderWidth: 2,
            tension: 0.3, hidden: estado.ocultos.has(s.slug), order: i + 1, segment: tracejarUltimo(d.periodos.length),
        })));

        estado.graficos.push(new Chart(el.canvas, {
            type: 'line',
            data: { labels: d.rotulos, datasets },
            options: {
                maintainAspectRatio: false,
                animation: { duration: 600 },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: tooltip(t, { itemSort: (a, b) => b.raw - a.raw }, d),
                },
                scales: eixos(t, muitos),
            },
            plugins: [crosshair],
        }));
    }

    function multiplos(d, series) {
        const t = tema();
        const n = d.periodos.length;
        el.multiplos.innerHTML = series.map((s) => {
            const ref = estado.parcial ? n - 2 : n - 1;
            const ultimo = s.valores[ref] ?? 0;
            const anterior = s.valores[ref - 1] ?? 0;
            const variacao = anterior > 0 ? Math.round(((ultimo - anterior) / anterior) * 100) : null;
            const seta = variacao === null ? '' : variacao > 0 ? 'trending-up' : variacao < 0 ? 'trending-down' : 'minus';
            return `
            <article class="bg-card border border-border rounded-2xl shadow-sm p-5 flex flex-col">
                <header class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full" style="background:${cor(s.cor)}"></span>
                        <h3 class="font-heading font-bold text-foreground">${s.nome}</h3>
                    </div>
                    ${variacao === null ? '' : `<span class="inline-flex items-center gap-1 text-xs font-semibold text-muted-foreground" title="Último ${NOME_PERIODO[d.agrupamento]} completo em relação ao anterior"><i data-lucide="${seta}" class="w-3.5 h-3.5"></i>${variacao > 0 ? '+' : ''}${variacao}%</span>`}
                </header>
                <p class="mt-3 font-heading text-3xl font-bold text-foreground tabular-nums">${fmt(s.total)}</p>
                <p class="text-xs text-muted-foreground">no período · ${fmt(ultimo)} no último ${NOME_PERIODO[d.agrupamento]}${estado.parcial ? ' completo' : ''} (${d.rotulos[ref] || ''})</p>
                <div class="relative h-36 mt-4"><canvas data-slug="${s.slug}" role="img" aria-label="Evolução de ${s.nome}"></canvas></div>
            </article>`;
        }).join('');

        series.forEach((s) => {
            const canvas = el.multiplos.querySelector(`canvas[data-slug="${s.slug}"]`);
            const ctx = canvas.getContext('2d');
            const g = ctx.createLinearGradient(0, 0, 0, canvas.clientHeight || 144);
            g.addColorStop(0, alfa(cor(s.cor), 0.28));
            g.addColorStop(1, alfa(cor(s.cor), 0));
            estado.graficos.push(new Chart(canvas, {
                type: 'line',
                data: { labels: d.rotulos, datasets: [{ label: s.nome, data: s.valores, borderColor: cor(s.cor), backgroundColor: g, fill: true, borderWidth: 2, pointRadius: 0, pointHoverRadius: 5, pointBackgroundColor: cor(s.cor), pointBorderColor: t.superficie, pointBorderWidth: 2, tension: 0.3, segment: tracejarUltimo(n) }] },
                options: {
                    maintainAspectRatio: false,
                    animation: { duration: 500 },
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false }, tooltip: tooltip(t, { displayColors: false }, d) },
                    scales: {
                        x: { display: false },
                        y: { beginAtZero: true, border: { display: false }, grid: { color: t.grade }, ticks: { color: t.tinta2, maxTicksLimit: 4, callback: (v) => fmt(v), font: { size: 11 } } },
                    },
                },
                plugins: [crosshair],
            }));
        });
    }

    function tabela(d, series) {
        el.cabecalho.innerHTML = `<tr class="text-left text-xs uppercase tracking-wider text-muted-foreground">
            <th class="py-3 px-5 md:px-8">Período</th>
            ${series.map((s) => `<th class="py-3 px-3 text-right"><span class="inline-block w-2 h-2 rounded-full mr-1.5" style="background:${cor(s.cor)}"></span>${s.nome}</th>`).join('')}
            <th class="py-3 px-3 text-right text-foreground">Total</th>
        </tr>`;
        const linhas = [];
        for (let i = d.periodos.length - 1; i >= 0; i--) {
            linhas.push(`<tr class="border-t border-border/60 hover:bg-muted/50">
                <td class="py-2 px-5 md:px-8 font-semibold text-foreground">${d.rotulos[i]}${estado.parcial && i === d.periodos.length - 1 ? ' <span class="text-xs font-normal text-muted-foreground">(em andamento)</span>' : ''}</td>
                ${series.map((s) => `<td class="py-2 px-3 text-right tabular-nums text-muted-foreground">${fmt(s.valores[i])}</td>`).join('')}
                <td class="py-2 px-3 text-right tabular-nums font-bold text-foreground">${fmt(d.total.valores[i])}</td>
            </tr>`);
        }
        el.corpo.innerHTML = linhas.join('');
    }

    function tooltip(t, extra, d) {
        const n = d ? d.periodos.length : 0;
        return Object.assign({
            backgroundColor: t.superficie, titleColor: t.tinta, bodyColor: t.tinta, borderColor: t.grade, borderWidth: 1,
            padding: 12, cornerRadius: 10, boxWidth: 8, boxHeight: 8, usePointStyle: true, boxPadding: 4,
            titleFont: { family: 'Montserrat, sans-serif', weight: '700' }, bodyFont: { family: 'Lato, sans-serif' },
            callbacks: {
                title: (itens) => itens.length ? itens[0].label + (estado.parcial && itens[0].dataIndex === n - 1 ? ' (em andamento)' : '') : '',
                label: (c) => ` ${c.dataset.label}: ${fmt(c.raw)}`,
            },
        }, extra || {});
    }

    function eixos(t, muitos) {
        return {
            x: { grid: { display: false }, border: { color: t.grade }, ticks: { color: t.tinta2, maxRotation: 0, autoSkip: true, maxTicksLimit: muitos ? 10 : 14, font: { family: 'Lato, sans-serif' } } },
            y: { beginAtZero: true, border: { display: false }, grid: { color: t.grade }, ticks: { color: t.tinta2, callback: (v) => fmt(v), font: { family: 'Lato, sans-serif' } } },
        };
    }

    /* O último mês/ano ainda em andamento é desenhado tracejado e fica fora da comparação de variação. */
    function ultimoParcial(d) {
        const ultima = d.historico && d.historico.ultimaData;
        if (!ultima || d.agrupamento === 'dia') {
            return false;
        }
        const fim = new Date((d.ate < ultima ? d.ate : ultima) + 'T12:00:00');
        if (d.agrupamento === 'mes') {
            const seguinte = new Date(fim);
            seguinte.setDate(fim.getDate() + 1);
            return seguinte.getMonth() === fim.getMonth();
        }
        return !(fim.getMonth() === 11 && fim.getDate() === 31);
    }

    function tracejarUltimo(n) {
        return { borderDash: (c) => (estado.parcial && c.p1DataIndex === n - 1 ? [5, 5] : undefined) };
    }

    function rotuloData(iso) {
        return iso ? iso.split('-').reverse().join('/') : '';
    }

    /* Eventos */
    document.querySelectorAll('[data-agrupamento]').forEach((b) => b.addEventListener('click', () => {
        estado.agrupamento = b.dataset.agrupamento;
        estado.periodo = PADRAO[estado.agrupamento];
        document.querySelectorAll('[data-agrupamento]').forEach((x) => x.setAttribute('aria-checked', x === b ? 'true' : 'false'));
        preencherPeriodos();
        carregar();
    }));
    el.periodo.addEventListener('change', () => {
        estado.periodo = el.periodo.value;
        carregar();
    });
    el.legenda.addEventListener('click', (e) => {
        const chip = e.target.closest('[data-slug]');
        if (!chip) {
            return;
        }
        const slug = chip.dataset.slug;
        estado.ocultos.has(slug) ? estado.ocultos.delete(slug) : estado.ocultos.add(slug);
        chip.setAttribute('aria-pressed', estado.ocultos.has(slug) ? 'false' : 'true');
        const grafico = estado.graficos[0];
        grafico.data.datasets.forEach((ds) => { ds.hidden = estado.ocultos.has(ds.slug); });
        grafico.update();
    });

    /* Redesenha ao trocar o tema claro/escuro. */
    new MutationObserver(() => desenhar()).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    /* Contador do número de destaque. */
    document.querySelectorAll('[data-contador]').forEach((n) => {
        const alvo = parseInt(n.dataset.contador, 10);
        if (!alvo || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        const inicio = performance.now();
        const passo = (agora) => {
            const p = Math.min(1, (agora - inicio) / 1400);
            n.textContent = fmt(Math.round(alvo * (1 - Math.pow(1 - p, 3))));
            if (p < 1) {
                requestAnimationFrame(passo);
            }
        };
        requestAnimationFrame(passo);
    });

    preencherPeriodos();
    carregar();
})();
