import { Controller } from '@hotwired/stimulus';
import { waitForGlobal } from '../lib/wait_for_global.js';

/*
 * Troca os <i data-lucide="..."> pelos ícones SVG do Lucide no site público.
 *
 * Fica no <body> do layout público e roda a cada página exibida, inclusive quando ela não vem de uma
 * carga completa. O Lucide é carregado do CDN (<script defer> no fim do layout) e pode terminar de
 * carregar depois deste controller conectar.
 */
export default class extends Controller {
    connect() {
        waitForGlobal('lucide')
            .then((lucide) => {
                if (this.element.isConnected) {
                    lucide.createIcons();
                }
            })
            .catch((error) => console.error(error));
    }
}
