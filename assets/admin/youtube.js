/**
 * Reconhecimento de links do YouTube no navegador (pré-visualização instantânea).
 * Espelha App\Service\Gallery\YouTubeUrlParser — o servidor sempre valida de novo.
 */
const ID_PATTERN = /^[A-Za-z0-9_-]{11}$/;
const HOSTS = ['youtube.com', 'youtube-nocookie.com', 'youtu.be'];

export function extractYouTubeId(input) {
    let value = (input || '').trim();

    if (!value || /\s/.test(value)) {
        return null;
    }

    if (!/^[a-z][a-z0-9+.-]*:\/\//i.test(value)) {
        value = `https://${value.replace(/^\/+/, '')}`;
    }

    let url;
    try {
        url = new URL(value);
    } catch (e) {
        return null;
    }

    if (!['http:', 'https:'].includes(url.protocol)) {
        return null;
    }

    const host = url.hostname.toLowerCase().replace(/^(?:www\.|m\.|music\.)/, '');
    if (!HOSTS.includes(host)) {
        return null;
    }

    const path = url.pathname.replace(/\/+$/, '');
    let candidate = null;

    if (host === 'youtu.be') {
        candidate = path.replace(/^\//, '').split('/')[0];
    } else {
        const match = path.match(/^\/(?:embed|shorts|live|v|e)\/([^/]+)/);
        if (match) {
            candidate = match[1];
        } else if (path === '/watch' || path === '') {
            candidate = url.searchParams.get('v');
        }
    }

    return candidate && ID_PATTERN.test(candidate) ? candidate : null;
}

/**
 * Separa um texto com vários links (um por linha) em [{ input, videoId }], sem repetições.
 */
export function parseYouTubeLinks(text) {
    const results = [];
    const seen = new Set();

    (text || '').split(/[\s,;]+/).filter(Boolean).forEach((input) => {
        const videoId = extractYouTubeId(input);
        const key = videoId || `invalid:${input}`;

        if (!seen.has(key)) {
            seen.add(key);
            results.push({ input, videoId });
        }
    });

    return results;
}

export function youTubeThumbnailUrl(videoId) {
    return `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`;
}
