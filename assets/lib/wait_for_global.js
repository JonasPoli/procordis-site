/**
 * Espera uma biblioteca carregada por <script> comum (CDN ou public/js) ficar disponível em window.
 *
 * Os controllers Stimulus podem conectar antes de esses scripts terminarem de carregar: na carga normal,
 * o módulo "app" roda antes dos <script defer> que vêm depois dele e, nas navegações do Turbo, os
 * <script> novos da página são carregados sem ordem garantida. Por isso o controller espera o global
 * em vez de depender de DOMContentLoaded (que não dispara de novo nas navegações do Turbo).
 *
 * waitForGlobal('tinymce').then((tinymce) => ...);
 */
export function waitForGlobal(name, { timeout = 15000, interval = 50 } = {}) {
    return new Promise((resolve, reject) => {
        if (window[name]) {
            resolve(window[name]);

            return;
        }

        const startedAt = Date.now();
        const timer = window.setInterval(() => {
            if (window[name]) {
                window.clearInterval(timer);
                resolve(window[name]);
            } else if (Date.now() - startedAt >= timeout) {
                window.clearInterval(timer);
                reject(new Error(`A biblioteca "${name}" não carregou em ${timeout / 1000} s.`));
            }
        }, interval);
    });
}
