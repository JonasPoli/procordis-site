import { Controller } from '@hotwired/stimulus';

/*
 * Contador de caracteres dos campos de SEO (título e descrição) do formulário de notícia.
 * Mostra quantos caracteres foram digitados e fica vermelho quando o texto passa do limite recomendado.
 *
 * <div data-controller="seo-counter" data-seo-counter-max-value="60">
 *     <input data-seo-counter-target="input" data-action="input->seo-counter#update">
 *     <span data-seo-counter-target="display">0</span>/60
 * </div>
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'display'];

    static values = { max: Number };

    connect() {
        this.update();
    }

    update() {
        const current = this.inputTarget.value.length;

        this.displayTarget.textContent = current;
        this.displayTarget.classList.toggle('text-red-500', current > this.maxValue);
    }
}
