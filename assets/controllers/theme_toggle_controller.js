import { Controller } from '@hotwired/stimulus';

const STORAGE_KEY = 'theme';

function storedTheme() {
    try {
        return localStorage.getItem(STORAGE_KEY) || 'light';
    } catch (error) {
        return 'light';
    }
}

function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
}

/*
 * Tema claro/escuro do site público e do painel (fica no <body> dos dois layouts).
 *
 * - A classe "dark" do <html> é aplicada logo no <head> por um script de uma linha, para a página não
 *   "piscar" no tema claro; aqui ela só é sincronizada com a preferência salva.
 * - Mostra o ícone certo (sol no tema escuro, lua no claro) em todos os botões .theme-toggle-btn.
 * - Ao clicar num botão com data-action="theme-toggle#toggle", salva o novo tema e recarrega a página
 *   para renderizar tudo de novo com as cores certas.
 */
export default class extends Controller {
    connect() {
        applyTheme(storedTheme());
        this.updateIcons();
    }

    toggle() {
        const theme = storedTheme() === 'dark' ? 'light' : 'dark';
        let saved = true;

        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (error) {
            // Sem localStorage (navegação privada restrita): troca só nesta página, sem recarregar.
            saved = false;
        }

        applyTheme(theme);
        this.updateIcons();

        // Recarrega para evitar variáveis de CSS desatualizadas (comportamento anterior).
        if (saved) {
            window.setTimeout(() => window.location.reload(), 50);
        }
    }

    updateIcons() {
        const isDark = document.documentElement.classList.contains('dark');

        this.element.querySelectorAll('.theme-toggle-sun').forEach((icon) => icon.classList.toggle('hidden', !isDark));
        this.element.querySelectorAll('.theme-toggle-moon').forEach((icon) => icon.classList.toggle('hidden', isDark));
    }
}
