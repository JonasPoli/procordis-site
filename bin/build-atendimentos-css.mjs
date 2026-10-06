#!/usr/bin/env node
/*
 * Gera public/css/atendimentos.css: utilitários Tailwind só das páginas de atendimentos,
 * restritos ao escopo .atd-escopo (CSS aninhado).
 *
 * Por que escopo: a página já carrega public/css/built.css e o app.css do importmap (outros builds).
 * Com as regras aninhadas em .atd-escopo, elas vencem esses builds pela especificidade só dentro das
 * áreas novas e não alteram o resto do site (menu, rodapé...).
 *
 * Uso: node bin/build-atendimentos-css.mjs
 */
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const ESCOPO = '.atd-escopo';
const tmp = 'var/atendimentos.tmp.css';
mkdirSync('var', { recursive: true });
execFileSync('npx', ['-y', '@tailwindcss/cli@4.1.18', '-i', './assets/styles/atendimentos.css', '-o', tmp, '--minify'], { stdio: 'inherit' });

const css = readFileSync(tmp, 'utf8').replace(/\/\*![^*]*\*\//, '');
const topo = [];
const escopo = [];
let i = 0;
while (i < css.length) {
    const abre = css.indexOf('{', i);
    if (abre === -1) {
        break;
    }
    let nivel = 0;
    let fim = abre;
    for (; fim < css.length; fim++) {
        if (css[fim] === '{') nivel++;
        else if (css[fim] === '}' && --nivel === 0) break;
    }
    const bloco = css.slice(i, fim + 1);
    const cabeca = bloco.slice(0, abre - i).trim();
    // Camadas (tema/propriedades), @property e @keyframes precisam ficar no nível de cima.
    (/^@(layer|property|keyframes)\b/.test(cabeca) ? topo : escopo).push(bloco);
    i = fim + 1;
}

writeFileSync('public/css/atendimentos.css',
    '/*! Gerado por bin/build-atendimentos-css.mjs (tailwindcss v4.1.18) - não editar */\n'
    + topo.join('') + `${ESCOPO}{${escopo.join('')}}\n`);
console.log(`public/css/atendimentos.css: ${topo.length} blocos globais, ${escopo.length} regras em ${ESCOPO}`);
