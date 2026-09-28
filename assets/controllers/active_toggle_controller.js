import { Controller } from '@hotwired/stimulus';
import { postJson } from '../admin/http.js';
import { showToast } from '../admin/toast.js';

/*
 * Chave "Ativo/Inativo" das listagens do painel (notícias, categorias e itens da galeria).
 * Alterna o status via AJAX e dá feedback visual imediato (toast + estado da chave).
 *
 * Dispara o evento "active-toggle:changed" com { active } para quem quiser reagir (ex.: galeria).
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['label'];

    static values = {
        url: String,
        token: String,
        active: Boolean,
        name: { type: String, default: 'Item' },
    };

    async toggle(event) {
        event.preventDefault();

        if (this.busy) {
            return;
        }

        const previous = this.activeValue;
        this.busy = true;
        this.element.setAttribute('aria-busy', 'true');
        // Atualização otimista: a chave muda na hora e volta se o servidor recusar.
        this.activeValue = !previous;

        try {
            const data = await postJson(this.urlValue, {}, this.tokenValue);
            this.activeValue = Boolean(data.active);
            showToast(data.message || (this.activeValue ? 'Ativado.' : 'Desativado.'));
            this.dispatch('changed', { detail: { active: this.activeValue } });
        } catch (error) {
            this.activeValue = previous;
            showToast(error.message || 'Não foi possível alterar o status.', 'error');
        } finally {
            this.busy = false;
            this.element.removeAttribute('aria-busy');
        }
    }

    activeValueChanged() {
        const active = this.activeValue;
        this.element.setAttribute('aria-checked', active ? 'true' : 'false');
        this.element.setAttribute('aria-label', `${this.nameValue}: ${active ? 'ativo' : 'inativo'}. Clique para alternar.`);
        this.element.title = `Clique para ${active ? 'desativar' : 'ativar'}`;

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = active ? 'Ativo' : 'Inativo';
        }
    }
}
