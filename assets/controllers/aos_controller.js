import { Controller } from '@hotwired/stimulus';
import { waitForGlobal } from '../lib/wait_for_global.js';

// Instância do AOS já inicializada nesta aba (AOS.init() registra listeners globais e não deve se repetir).
let initializedAos = null;

/*
 * Animações de entrada ao rolar a página (atributos data-aos) no site público.
 *
 * Fica no <body> do layout público. A biblioteca (public/js/simple-aos.js) é carregada com <script defer>
 * e pode terminar de carregar depois deste controller conectar. Na primeira página, chama AOS.init();
 * se o <body> for trocado sem recarregar a aba, só recalcula os elementos (AOS.refreshHard()), sem
 * registrar os listeners de rolagem de novo.
 */
export default class extends Controller {
    connect() {
        waitForGlobal('AOS')
            .then((AOS) => {
                if (!this.element.isConnected) {
                    return;
                }

                if (initializedAos === AOS) {
                    AOS.refreshHard();

                    return;
                }

                AOS.init();
                initializedAos = AOS;
            })
            .catch((error) => console.error(error));
    }
}
