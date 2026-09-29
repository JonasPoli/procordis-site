import { Controller } from '@hotwired/stimulus';
import { waitForGlobal } from '../lib/wait_for_global.js';

// Campos que recebem o editor: basta a classe "editor-html" no <textarea> (FormType ou template).
const EDITOR_SELECTOR = '.editor-html';

const EDITOR_CONFIG = {
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
    branding: false,
    promotion: false,
    height: 500,
    setup(editor) {
        // Mantém o <textarea> sempre atualizado com o conteúdo do editor.
        editor.on('change', () => editor.save());
    },
};

/*
 * Editor de texto (TinyMCE) nos campos com a classe "editor-html" do painel.
 *
 * Fica no <body> do layout do painel: a cada página (carga normal ou navegação do Turbo) cria os
 * editores e, ao sair da página, remove-os. Antes de o Turbo guardar a página no cache (usado ao
 * voltar/avançar no histórico), os editores também são removidos e o conteúdo volta para o <textarea>;
 * assim a cópia em cache não leva um editor "morto" e ele é recriado quando a página é restaurada.
 *
 * O TinyMCE vem do CDN (<script> no <head> do layout) e pode terminar de carregar depois deste controller.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.editors = [];
        this.generation = 0;
        this.beforeCache = () => this.removeEditors();
        document.addEventListener('turbo:before-cache', this.beforeCache);

        // Na pré-visualização do cache o Turbo já vai trocar a página pela versão nova: não cria editor.
        if (!this.element.querySelector(EDITOR_SELECTOR) || document.documentElement.hasAttribute('data-turbo-preview')) {
            return;
        }

        const generation = this.generation;
        waitForGlobal('tinymce')
            .then((tinymce) => this.createEditors(tinymce, generation))
            .catch((error) => console.error(error));
    }

    disconnect() {
        document.removeEventListener('turbo:before-cache', this.beforeCache);
        this.removeEditors();
    }

    async createEditors(tinymce, generation) {
        if (generation !== this.generation || !this.element.isConnected) {
            return;
        }

        const fields = Array.from(this.element.querySelectorAll(EDITOR_SELECTOR));

        const results = await Promise.all(fields.map((field) => {
            // Editor que ficou registrado com o mesmo id (de uma página anterior): descarta antes de recriar.
            const stale = field.id ? tinymce.get(field.id) : null;
            if (stale) {
                stale.remove();
            }

            return tinymce.init({ ...EDITOR_CONFIG, target: field });
        }));

        const editors = results.flat().filter(Boolean);

        // A página mudou enquanto o TinyMCE inicializava: não deixa editores órfãos.
        if (generation !== this.generation) {
            editors.forEach((editor) => editor.remove());

            return;
        }

        this.editors.push(...editors);
    }

    removeEditors() {
        this.generation += 1;

        this.editors.forEach((editor) => {
            if (editor.removed) {
                return;
            }

            try {
                editor.save();
                editor.remove();
            } catch (error) {
                console.error(error);
            }
        });

        this.editors = [];
    }
}
