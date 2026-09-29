import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';
import { messageForStatus, postJson } from '../admin/http.js';
import { showToast } from '../admin/toast.js';
import { parseYouTubeLinks, youTubeThumbnailUrl } from '../admin/youtube.js';

const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
const PARALLEL_UPLOADS = 3;

function formatBytes(bytes) {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',').replace(',0', '')} MB`;
    }

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

// Escapa texto para uso em innerHTML, inclusive dentro de atributos (aspas também são escapadas).
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/*
 * Gerenciador da galeria de uma notícia no painel:
 * upload múltiplo (arrastar/soltar + seleção) com progresso por arquivo, vídeos do YouTube com
 * pré-visualização, legenda inline, ativar/desativar, reordenação por arrasto e exclusão.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = [
        'dropzone', 'fileInput', 'uploads', 'list', 'empty', 'count', 'item', 'caption',
        'youtubeInput', 'youtubePreviews', 'youtubeSubmit', 'youtubeStatus',
    ];

    static values = {
        uploadUrl: String,
        previewUrl: String,
        youtubeUrl: String,
        reorderUrl: String,
        token: String,
        maxBytes: Number,
    };

    connect() {
        this.queue = [];
        this.activeUploads = 0;
        this.videoCards = new Map();
        this.previewDebounce = null;
        this.addingVideos = false;

        this.sortable = Sortable.create(this.listTarget, {
            handle: '[data-drag-handle]',
            draggable: '[data-news-gallery-admin-target="item"]',
            animation: 180,
            ghostClass: 'gallery-sortable-ghost',
            chosenClass: 'gallery-sortable-chosen',
            onEnd: (event) => {
                if (event.oldIndex !== event.newIndex) {
                    this.saveOrder();
                }
            },
        });

        // Evita que o navegador abra a imagem se ela for solta fora da área de upload.
        // Campos de arquivo (ex.: "Imagem de Capa" do formulário) continuam aceitando arquivos soltos neles.
        this.preventWindowDrop = (event) => {
            if (event.target instanceof Element && event.target.closest('input[type="file"]')) {
                return;
            }

            if (event.dataTransfer && Array.from(event.dataTransfer.types || []).includes('Files')) {
                event.preventDefault();
            }
        };
        window.addEventListener('dragover', this.preventWindowDrop);
        window.addEventListener('drop', this.preventWindowDrop);
    }

    disconnect() {
        this.sortable?.destroy();
        window.removeEventListener('dragover', this.preventWindowDrop);
        window.removeEventListener('drop', this.preventWindowDrop);
        clearTimeout(this.previewDebounce);
    }

    // ------------------------------------------------------------------
    // Upload de imagens
    // ------------------------------------------------------------------

    openFilePicker() {
        this.fileInputTarget.click();
    }

    filesSelected(event) {
        this.enqueue(Array.from(event.target.files || []));
        event.target.value = '';
    }

    dragEnter(event) {
        if (!this.hasFiles(event)) {
            return;
        }
        event.preventDefault();
        this.dropzoneTarget.dataset.dragging = 'true';
    }

    dragOver(event) {
        if (!this.hasFiles(event)) {
            return;
        }
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        this.dropzoneTarget.dataset.dragging = 'true';
    }

    dragLeave(event) {
        if (!this.dropzoneTarget.contains(event.relatedTarget)) {
            delete this.dropzoneTarget.dataset.dragging;
        }
    }

    drop(event) {
        if (!this.hasFiles(event)) {
            return;
        }
        event.preventDefault();
        delete this.dropzoneTarget.dataset.dragging;
        this.enqueue(Array.from(event.dataTransfer.files || []));
    }

    hasFiles(event) {
        return Boolean(event.dataTransfer) && Array.from(event.dataTransfer.types || []).includes('Files');
    }

    enqueue(files) {
        if (!files.length) {
            return;
        }

        files.forEach((file) => {
            const row = this.createUploadRow(file);
            const error = this.validateFile(file);

            if (error) {
                this.setRowState(row, 'error', error);

                return;
            }

            this.queue.push({ file, row });
        });

        this.processQueue();
    }

    validateFile(file) {
        const extension = (file.name.split('.').pop() || '').toLowerCase();

        if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTENSIONS.includes(extension)) {
            return 'Formato não aceito. Envie imagens JPG, PNG ou WebP.';
        }

        if (this.maxBytesValue && file.size > this.maxBytesValue) {
            return `A imagem tem ${formatBytes(file.size)}; o limite é de ${formatBytes(this.maxBytesValue)} por arquivo.`;
        }

        if (file.size === 0) {
            return 'O arquivo está vazio.';
        }

        return null;
    }

    processQueue() {
        while (this.activeUploads < PARALLEL_UPLOADS && this.queue.length) {
            const entry = this.queue.shift();
            this.activeUploads += 1;
            this.upload(entry).finally(() => {
                this.activeUploads -= 1;
                this.processQueue();
            });
        }
    }

    upload({ file, row }) {
        return new Promise((resolve) => {
            const xhr = new XMLHttpRequest();
            const data = new FormData();
            data.append('file', file);

            xhr.open('POST', this.uploadUrlValue);
            xhr.setRequestHeader('X-CSRF-Token', this.tokenValue);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.responseType = 'json';

            this.setRowState(row, 'uploading', 'Enviando… 0%', 0);

            xhr.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable) {
                    return;
                }
                const percent = Math.round((event.loaded / event.total) * 100);
                if (percent >= 100) {
                    this.setRowState(row, 'processing', 'Gerando versões da imagem…', 100);
                } else {
                    this.setRowState(row, 'uploading', `Enviando… ${percent}%`, percent);
                }
            });

            xhr.addEventListener('load', () => {
                const response = xhr.response && typeof xhr.response === 'object' ? xhr.response : null;
                const isJson = (xhr.getResponseHeader('content-type') || '').includes('application/json');

                if (xhr.status >= 200 && xhr.status < 300 && response?.html) {
                    this.insertItem(response.html, response.item?.position);
                    this.setRowState(row, 'done', 'Imagem adicionada à galeria.', 100);
                    window.setTimeout(() => this.removeRow(row), 2500);
                } else {
                    const message = response?.error
                        || (!isJson && xhr.responseURL.includes('/login') ? messageForStatus(401) : messageForStatus(xhr.status));
                    this.setRowState(row, 'error', message, 100, xhr.status >= 500 ? () => this.retry(file, row) : null);
                }
                resolve();
            });

            xhr.addEventListener('error', () => {
                this.setRowState(row, 'error', messageForStatus(0), 100, () => this.retry(file, row));
                resolve();
            });

            xhr.send(data);
        });
    }

    retry(file, row) {
        this.queue.push({ file, row });
        this.setRowState(row, 'waiting', 'Aguardando…', 0);
        this.processQueue();
    }

    createUploadRow(file) {
        const row = document.createElement('li');
        row.className = 'rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-800/60 px-4 py-3 text-sm shadow-sm';
        row.innerHTML = `
            <div class="flex items-center justify-between gap-3">
                <span class="truncate font-medium text-slate-700 dark:text-slate-200">${escapeHtml(file.name)}</span>
                <span class="shrink-0 text-xs text-slate-400">${formatBytes(file.size)}</span>
            </div>
            <div class="mt-2 h-2 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Progresso do envio de ${escapeHtml(file.name)}">
                <div data-bar class="h-full w-0 rounded-full bg-blue-600 transition-all duration-200"></div>
            </div>
            <div class="mt-1.5 flex items-center justify-between gap-2">
                <p data-message class="text-xs text-slate-500 dark:text-slate-400">Aguardando…</p>
                <div data-actions class="flex items-center gap-2 shrink-0"></div>
            </div>`;
        this.uploadsTarget.appendChild(row);

        return row;
    }

    setRowState(row, state, message, percent = null, retry = null) {
        const bar = row.querySelector('[data-bar]');
        const progress = row.querySelector('[role="progressbar"]');
        const text = row.querySelector('[data-message]');
        const actions = row.querySelector('[data-actions]');

        if (percent !== null) {
            bar.style.width = `${percent}%`;
            progress.setAttribute('aria-valuenow', String(percent));
        }

        bar.classList.remove('bg-blue-600', 'bg-emerald-500', 'bg-red-500', 'animate-pulse');
        text.classList.remove('text-slate-500', 'text-red-600', 'text-emerald-600', 'dark:text-slate-400');

        const styles = {
            waiting: ['bg-blue-600', 'text-slate-500'],
            uploading: ['bg-blue-600', 'text-slate-500'],
            processing: ['bg-blue-600', 'text-slate-500'],
            done: ['bg-emerald-500', 'text-emerald-600'],
            error: ['bg-red-500', 'text-red-600'],
        }[state];

        bar.classList.add(styles[0]);
        if (state === 'processing') {
            bar.classList.add('animate-pulse');
        }
        if (state === 'error' && percent === null) {
            bar.style.width = '100%';
        }
        text.classList.add(styles[1]);
        text.textContent = message;

        actions.innerHTML = '';
        if (state === 'error') {
            if (retry) {
                const retryButton = document.createElement('button');
                retryButton.type = 'button';
                retryButton.className = 'text-xs font-semibold text-blue-600 hover:underline';
                retryButton.textContent = 'Tentar novamente';
                retryButton.addEventListener('click', retry, { once: true });
                actions.appendChild(retryButton);
            }

            const dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.className = 'text-xs font-semibold text-slate-500 hover:text-slate-800 hover:underline';
            dismiss.textContent = 'Dispensar';
            dismiss.addEventListener('click', () => this.removeRow(row), { once: true });
            actions.appendChild(dismiss);
        }
    }

    removeRow(row) {
        row.classList.add('opacity-0', 'transition-opacity', 'duration-300');
        window.setTimeout(() => row.remove(), 300);
    }

    // ------------------------------------------------------------------
    // Vídeos do YouTube
    // ------------------------------------------------------------------

    youtubeInput() {
        clearTimeout(this.previewDebounce);
        this.previewDebounce = window.setTimeout(() => this.refreshVideoPreviews(), 400);
    }

    youtubePaste() {
        clearTimeout(this.previewDebounce);
        // Espera o texto colado entrar no campo.
        window.setTimeout(() => this.refreshVideoPreviews(), 0);
    }

    refreshVideoPreviews() {
        const parsed = parseYouTubeLinks(this.youtubeInputTarget.value);
        const keys = new Set();
        const toFetch = [];

        parsed.forEach(({ input, videoId }) => {
            const key = videoId || `invalid:${input}`;
            keys.add(key);

            if (this.videoCards.has(key)) {
                return;
            }

            const card = this.createVideoCard(input, videoId);
            this.videoCards.set(key, card);

            if (videoId) {
                toFetch.push({ input, videoId });
            }
        });

        // Remove cartões de links que saíram do campo.
        this.videoCards.forEach((card, key) => {
            if (!keys.has(key) && !card.adding) {
                card.element.remove();
                this.videoCards.delete(key);
            }
        });

        if (toFetch.length) {
            this.fetchVideoPreviews(toFetch);
        }

        this.updateVideoSubmit();
    }

    async fetchVideoPreviews(entries) {
        try {
            const data = await postJson(this.previewUrlValue, { urls: entries.map((entry) => entry.input) }, this.tokenValue);

            (data.results || []).forEach((result) => {
                const card = result.videoId ? this.videoCards.get(result.videoId) : null;
                if (!card) {
                    return;
                }

                card.loading = false;

                if (result.error) {
                    this.setVideoCardError(card, result.error);

                    return;
                }

                card.title = result.title;
                card.element.querySelector('[data-title]').textContent = result.title;
                const caption = card.element.querySelector('[data-caption]');
                caption.disabled = false;
                if (!caption.dataset.edited) {
                    caption.value = result.title;
                }

                const status = card.element.querySelector('[data-status]');
                if (result.duplicate) {
                    status.className = 'text-xs mt-1 text-amber-600 dark:text-amber-400';
                    status.textContent = 'Atenção: este vídeo já está na galeria desta notícia.';
                } else {
                    status.className = 'text-xs mt-1 text-emerald-600 dark:text-emerald-400';
                    status.textContent = 'Pronto para adicionar. A legenda pode ser editada.';
                }
            });
        } catch (error) {
            entries.forEach(({ videoId }) => {
                const card = this.videoCards.get(videoId);
                if (card) {
                    card.loading = false;
                    this.setVideoCardError(card, error.message);
                }
            });
        }

        this.updateVideoSubmit();
    }

    createVideoCard(input, videoId) {
        const element = document.createElement('li');
        element.className = 'flex gap-3 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-800/60 p-3 shadow-sm transition-opacity';

        const removeButton = `
            <button type="button" data-remove class="self-start p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-white/10" aria-label="Remover este link da lista" title="Remover da lista">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>`;

        if (!videoId) {
            element.classList.add('border-red-200', 'dark:border-red-900/60');
            element.innerHTML = `
                <span class="flex items-center justify-center w-10 h-10 shrink-0 rounded-full bg-red-50 text-red-600 dark:bg-red-900/30" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-red-700 dark:text-red-300">Link do YouTube não reconhecido</p>
                    <p class="text-xs text-slate-500 truncate">${escapeHtml(input)}</p>
                </div>
                ${removeButton}`;
        } else {
            element.innerHTML = `
                <div class="relative w-32 sm:w-36 shrink-0 aspect-video rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-900">
                    <img src="${youTubeThumbnailUrl(videoId)}" alt="" class="w-full h-full object-cover" loading="lazy">
                    <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                        <span class="flex items-center justify-center w-9 h-9 rounded-full bg-red-600/90 text-white shadow">
                            <svg class="w-4 h-4 ml-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </span>
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p data-title class="text-sm font-semibold text-slate-700 dark:text-slate-100 line-clamp-2">Buscando título…</p>
                    <p class="text-[11px] text-slate-400 truncate">${escapeHtml(input)}</p>
                    <label class="sr-only" for="yt-caption-${videoId}">Legenda do vídeo</label>
                    <input id="yt-caption-${videoId}" data-caption type="text" maxlength="500" disabled placeholder="Legenda do vídeo"
                           class="mt-2 w-full rounded-lg border border-slate-300 dark:border-[#3d4d60] bg-white dark:bg-[#1D2A39] px-2 py-1.5 text-sm text-slate-700 dark:text-white focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 disabled:opacity-60">
                    <p data-status class="text-xs mt-1 text-slate-400">Consultando o YouTube…</p>
                </div>
                ${removeButton}`;

            const caption = element.querySelector('[data-caption]');
            caption.addEventListener('input', () => {
                caption.dataset.edited = 'true';
            });
            caption.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    this.addVideos();
                }
            });
        }

        const card = { element, input, videoId, loading: Boolean(videoId), error: !videoId, adding: false, title: null };

        element.querySelector('[data-remove]').addEventListener('click', () => this.removeVideoCard(card));
        this.youtubePreviewsTarget.appendChild(element);

        return card;
    }

    setVideoCardError(card, message) {
        card.error = true;
        card.element.classList.add('border-red-200', 'dark:border-red-900/60');
        const title = card.element.querySelector('[data-title]');
        if (title && title.textContent === 'Buscando título…') {
            title.textContent = 'Vídeo indisponível';
        }
        const status = card.element.querySelector('[data-status]');
        if (status) {
            status.className = 'text-xs mt-1 text-red-600 dark:text-red-400';
            status.textContent = message;
        }
        const caption = card.element.querySelector('[data-caption]');
        if (caption) {
            caption.disabled = true;
        }
    }

    removeVideoCard(card) {
        const key = card.videoId || `invalid:${card.input}`;
        card.element.remove();
        this.videoCards.delete(key);
        this.syncYoutubeTextarea();
        this.updateVideoSubmit();
    }

    syncYoutubeTextarea() {
        this.youtubeInputTarget.value = Array.from(this.videoCards.values()).map((card) => card.input).join('\n');
    }

    readyVideoCards() {
        return Array.from(this.videoCards.values()).filter((card) => card.videoId && !card.loading && !card.error && !card.adding);
    }

    updateVideoSubmit() {
        const ready = this.readyVideoCards().length;
        const loading = Array.from(this.videoCards.values()).some((card) => card.loading);

        this.youtubeSubmitTarget.disabled = this.addingVideos || ready === 0;
        this.youtubeSubmitTarget.textContent = ready > 1 ? `Adicionar ${ready} vídeos` : 'Adicionar vídeo';
        this.youtubeStatusTarget.textContent = loading ? 'Consultando o YouTube…' : '';
    }

    async addVideos() {
        const cards = this.readyVideoCards();
        if (!cards.length || this.addingVideos) {
            return;
        }

        this.addingVideos = true;
        this.updateVideoSubmit();
        let added = 0;

        for (const card of cards) {
            card.adding = true;
            const status = card.element.querySelector('[data-status]');
            const caption = card.element.querySelector('[data-caption]');
            status.className = 'text-xs mt-1 text-blue-600 dark:text-blue-400';
            status.textContent = 'Adicionando e gerando a capa…';
            caption.disabled = true;

            try {
                const data = await postJson(this.youtubeUrlValue, { url: card.input, caption: caption.value }, this.tokenValue);
                this.insertItem(data.html, data.item?.position);
                this.videoCards.delete(card.videoId);
                card.element.remove();
                added += 1;
            } catch (error) {
                card.adding = false;
                this.setVideoCardError(card, error.message);
            }
        }

        this.addingVideos = false;
        this.syncYoutubeTextarea();
        this.updateVideoSubmit();

        if (added) {
            showToast(added > 1 ? `${added} vídeos adicionados à galeria.` : 'Vídeo adicionado à galeria.');
        }
    }

    // ------------------------------------------------------------------
    // Itens da galeria
    // ------------------------------------------------------------------

    insertItem(html, position = null) {
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const element = template.content.firstElementChild;

        // Mantém a ordem do servidor mesmo quando envios paralelos terminam fora de ordem.
        const next = position === null || position === undefined
            ? null
            : this.itemTargets.find((item) => Number(item.dataset.position) > Number(position));

        if (next) {
            this.listTarget.insertBefore(element, next);
        } else {
            this.listTarget.appendChild(element);
        }

        this.updateCount();
    }

    captionInput(event) {
        const status = this.statusFor(event.target);
        status.className = 'gallery-caption-status min-h-[1rem] text-[11px] font-medium text-slate-400';
        status.textContent = event.target.value.trim() !== (event.target.dataset.original || '')
            ? 'Alterações não salvas (Enter para salvar)'
            : '';
    }

    captionKeydown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            event.target.blur();
        } else if (event.key === 'Escape') {
            event.target.value = event.target.dataset.original || '';
            this.statusFor(event.target).textContent = '';
            event.target.blur();
        }
    }

    async saveCaption(event) {
        const field = event.target;
        const item = field.closest('[data-news-gallery-admin-target="item"]');
        const value = field.value.trim();

        if (!item || value === (field.dataset.original || '')) {
            return;
        }

        const status = this.statusFor(field);
        status.className = 'gallery-caption-status min-h-[1rem] text-[11px] font-medium text-blue-600';
        status.textContent = 'Salvando…';

        try {
            const data = await postJson(item.dataset.captionUrl, { caption: value }, this.tokenValue);
            field.dataset.original = data.caption || '';
            if (document.activeElement !== field) {
                field.value = data.caption || '';
            }
            const image = item.querySelector('img');
            if (image && data.caption) {
                image.alt = data.caption;
            }
            status.className = 'gallery-caption-status min-h-[1rem] text-[11px] font-medium text-emerald-600';
            status.textContent = 'Legenda salva ✓';
            window.setTimeout(() => {
                if (status.textContent === 'Legenda salva ✓') {
                    status.textContent = '';
                }
            }, 2500);
        } catch (error) {
            status.className = 'gallery-caption-status min-h-[1rem] text-[11px] font-medium text-red-600';
            status.textContent = error.message;
            showToast(error.message, 'error');
        }
    }

    statusFor(field) {
        return field.closest('[data-news-gallery-admin-target="item"]').querySelector('.gallery-caption-status');
    }

    itemToggled(event) {
        const item = event.currentTarget;
        const active = Boolean(event.detail?.active);
        item.classList.toggle('is-inactive', !active);
        item.querySelector('.gallery-inactive-badge')?.classList.toggle('hidden', active);
    }

    async deleteItem(event) {
        const item = event.currentTarget.closest('[data-news-gallery-admin-target="item"]');
        if (!item) {
            return;
        }

        const ok = window.confirm('Excluir este item da galeria?\n\nOs arquivos gerados também serão apagados. Esta ação não pode ser desfeita.');
        if (!ok) {
            return;
        }

        item.setAttribute('aria-busy', 'true');
        item.classList.add('opacity-50', 'pointer-events-none');

        try {
            const data = await postJson(item.dataset.deleteUrl, {}, this.tokenValue);
            item.classList.add('scale-95', 'opacity-0');
            window.setTimeout(() => {
                item.remove();
                this.updateCount();
            }, 200);
            showToast(data.message || 'Item excluído da galeria.');
        } catch (error) {
            item.removeAttribute('aria-busy');
            item.classList.remove('opacity-50', 'pointer-events-none');
            showToast(error.message, 'error');
        }
    }

    moveBackward(event) {
        this.move(event.currentTarget, -1);
    }

    moveForward(event) {
        this.move(event.currentTarget, 1);
    }

    move(button, direction) {
        const item = button.closest('[data-news-gallery-admin-target="item"]');
        const sibling = direction < 0 ? item.previousElementSibling : item.nextElementSibling;

        if (!sibling) {
            return;
        }

        if (direction < 0) {
            this.listTarget.insertBefore(item, sibling);
        } else {
            this.listTarget.insertBefore(sibling, item);
        }

        button.focus();
        this.saveOrder();
    }

    async saveOrder() {
        const ids = this.itemTargets.map((item) => Number(item.dataset.id));
        this.itemTargets.forEach((item, index) => {
            item.dataset.position = String(index);
        });

        try {
            const data = await postJson(this.reorderUrlValue, { ids }, this.tokenValue);
            showToast(data.message || 'Nova ordem salva.');
        } catch (error) {
            showToast(`${error.message} A ordem não foi salva.`, 'error');
        }
    }

    updateCount() {
        const total = this.itemTargets.length;
        this.countTarget.textContent = `${total} ${total === 1 ? 'item' : 'itens'}`;
        this.emptyTarget.classList.toggle('hidden', total > 0);
    }
}
