/**
 * Requisições AJAX do painel com tratamento de erros em português.
 * O token CSRF (csrf_token('admin_ajax')) vai no cabeçalho X-CSRF-Token.
 */

export function messageForStatus(status) {
    switch (status) {
        case 401:
            return 'Sua sessão expirou. Entre novamente no painel.';
        case 403:
            return 'Você não tem permissão para esta ação ou a página está desatualizada. Recarregue e tente novamente.';
        case 404:
            return 'Item não encontrado. Ele pode ter sido excluído; recarregue a página.';
        case 413:
            return 'O arquivo é grande demais para o servidor.';
        case 0:
            return 'Falha de conexão. Verifique sua internet e tente novamente.';
        default:
            return `Erro inesperado (código ${status}). Tente novamente.`;
    }
}

/**
 * Faz um POST (JSON ou FormData) e devolve o JSON da resposta.
 * Lança Error com mensagem amigável em caso de falha.
 */
export async function postJson(url, body = null, token = null) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (token) {
        headers['X-CSRF-Token'] = token;
    }

    let payload = body;
    if (body !== null && !(body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    let response;
    try {
        response = await fetch(url, { method: 'POST', headers, body: payload, credentials: 'same-origin' });
    } catch (e) {
        throw new Error(messageForStatus(0));
    }

    return parseJsonResponse(response);
}

export async function parseJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    // Sessão expirada: o firewall redireciona para /login e a resposta vira HTML.
    if (!contentType.includes('application/json')) {
        if (response.redirected || response.url.includes('/login')) {
            throw new Error(messageForStatus(401));
        }
        throw new Error(messageForStatus(response.ok ? 500 : response.status));
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.error || messageForStatus(response.status));
    }

    return data;
}
