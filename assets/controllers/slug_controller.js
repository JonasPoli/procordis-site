import { Controller } from '@hotwired/stimulus';

// Palavras removidas do slug (mesma lista usada no servidor ao gerar o slug automaticamente).
const STOP_WORDS = ['o', 'a', 'os', 'as', 'um', 'uns', 'uma', 'umas', 'de', 'do', 'da', 'dos', 'das', 'em', 'no', 'na', 'nos', 'nas', 'por', 'pelo', 'pela', 'pelos', 'pelas', 'para', 'pra', 'pro', 'com', 'sem', 'sob', 'sobre', 'entre', 'ante', 'ate', 'contra', 'desde', 'e', 'ou', 'mas', 'nem', 'que', 'se', 'como', 'pois', 'porque', 'eu', 'voce', 'ele', 'ela', 'nos', 'eles', 'elas', 'meu', 'minha', 'teu', 'tua', 'seu', 'sua', 'nosso', 'nossa', 'esse', 'essa', 'isso', 'este', 'esta', 'isto', 'aquele', 'aquela', 'aquilo', 'qual', 'quais', 'quem', 'onde', 'ser', 'e', 'eh', 'foi', 'fom', 'sao', 'era', 'eram', 'estar', 'esta', 'estao', 'estava', 'estavam', 'ter', 'tem', 'tinha', 'tinham', 'teve', 'muito', 'mais', 'menos', 'ja', 'agora', 'tambe', 'so', 'talvez'];

export function generateSlug(text) {
    return text.toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .split(/\s+/)
        .filter((word) => !STOP_WORDS.includes(word))
        .join('-');
}

/*
 * Gera o slug (URL) a partir do título no formulário de notícia:
 * - ao sair do campo de título, preenche o slug se ele estiver vazio;
 * - o botão "Gerar automaticamente" sempre recria o slug a partir do título atual.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['title', 'slug'];

    fillIfEmpty() {
        if (!this.slugTarget.value) {
            this.slugTarget.value = generateSlug(this.titleTarget.value);
        }
    }

    generate() {
        this.slugTarget.value = generateSlug(this.titleTarget.value);
    }
}
