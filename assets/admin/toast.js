/**
 * Avisos rápidos (toasts) do painel, usados como feedback das ações AJAX.
 *
 * showToast('Legenda salva.');
 * showToast('Não foi possível salvar.', 'error');
 */
const CONTAINER_ID = 'admin-toasts';

function getContainer() {
    let container = document.getElementById(CONTAINER_ID);

    if (!container) {
        container = document.createElement('div');
        container.id = CONTAINER_ID;
        container.className = 'fixed bottom-6 right-6 z-[100] flex flex-col items-end gap-2 pointer-events-none max-w-sm';
        container.setAttribute('role', 'status');
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
    }

    return container;
}

export function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const isError = type === 'error';

    toast.className = [
        'pointer-events-auto flex items-start gap-2 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white',
        'transition-all duration-300 translate-y-2 opacity-0',
        isError ? 'bg-red-600' : 'bg-emerald-600',
    ].join(' ');
    toast.textContent = message;

    getContainer().appendChild(toast);
    requestAnimationFrame(() => toast.classList.remove('translate-y-2', 'opacity-0'));

    window.setTimeout(() => {
        toast.classList.add('opacity-0');
        window.setTimeout(() => toast.remove(), 300);
    }, isError ? 6000 : 2800);
}
