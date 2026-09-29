import { Controller } from '@hotwired/stimulus';
import Swiper from 'swiper';
import { A11y, FreeMode, Keyboard, Navigation, Thumbs } from 'swiper/modules';
import PhotoSwipeLightbox from 'photoswipe/lightbox';

const DOWNLOAD_ICON = '<path d="M20.5 14.3 17.1 18V10h-2.2v7.9l-3.4-3.6L10 16l6 6.1 6-6.1ZM23 23H9v2h14Z" id="pswp__icn-download"/>';
const YOUTUBE_ICON = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 00.5 6.2 31.4 31.4 0 000 12a31.4 31.4 0 00.5 5.8 3 3 0 002.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 002.1-2.1A31.4 31.4 0 0024 12a31.4 31.4 0 00-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"/></svg>';

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/*
 * Galeria pública da notícia.
 * - Slideshow (Swiper) com faixa de miniaturas, setas, teclado e swipe; exibe as versões média/miniatura.
 * - Lightbox (PhotoSwipe 5) em tela cheia: usa a imagem já carregada como placeholder e carrega a versão
 *   grande (srcset média/grande); zoom por pinça, duplo clique/toque ou botão; legenda, contador,
 *   botão de download e ESC para fechar.
 * - Vídeos do YouTube: o iframe (youtube-nocookie.com) só é criado quando o slide fica ativo no lightbox
 *   e é removido ao trocar de slide ou fechar, o que interrompe a reprodução.
 */
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['main', 'thumbs', 'prev', 'next', 'counter'];

    connect() {
        const slidesCount = this.mainTarget.querySelectorAll('.swiper-slide').length;
        const speed = prefersReducedMotion() ? 0 : 450;

        if (this.hasThumbsTarget) {
            this.thumbsSwiper = new Swiper(this.thumbsTarget, {
                modules: [FreeMode],
                slidesPerView: 'auto',
                spaceBetween: 8,
                freeMode: { enabled: true, sticky: false },
                watchSlidesProgress: true,
                slideToClickedSlide: true,
                speed,
            });
        }

        this.mainSwiper = new Swiper(this.mainTarget, {
            modules: [A11y, Keyboard, Navigation, Thumbs],
            autoHeight: true,
            spaceBetween: 16,
            speed,
            keyboard: { enabled: true, onlyInViewport: true },
            navigation: this.hasPrevTarget
                ? { prevEl: this.prevTarget, nextEl: this.nextTarget, addIcons: false }
                : false,
            thumbs: this.thumbsSwiper ? { swiper: this.thumbsSwiper } : undefined,
            a11y: {
                prevSlideMessage: 'Item anterior da galeria',
                nextSlideMessage: 'Próximo item da galeria',
                firstSlideMessage: 'Este é o primeiro item',
                lastSlideMessage: 'Este é o último item',
                slideLabelMessage: '{{index}} de {{slidesLength}}',
                containerRoleDescriptionMessage: 'galeria',
                itemRoleDescriptionMessage: 'item',
            },
            on: {
                slideChange: (swiper) => this.updateCounter(swiper.activeIndex, slidesCount),
            },
        });

        this.initLightbox();
    }

    disconnect() {
        this.lightbox?.destroy();
        this.mainSwiper?.destroy(true, true);
        this.thumbsSwiper?.destroy(true, true);
    }

    updateCounter(index, total) {
        if (this.hasCounterTarget) {
            this.counterTarget.textContent = `${index + 1} / ${total}`;
        }
    }

    initLightbox() {
        const lightbox = new PhotoSwipeLightbox({
            gallery: this.mainTarget,
            children: 'a.news-gallery__media',
            pswpModule: () => import('photoswipe'),

            bgOpacity: 0.94,
            showHideAnimationType: prefersReducedMotion() ? 'none' : 'zoom',
            preload: [1, 2],
            loop: false,
            wheelToZoom: false,
            // Duplo clique (mouse) amplia; duplo toque e pinça são nativos no celular.
            imageClickAction: (point) => this.handleImageClick(point),
            tapAction: 'toggle-controls',
            doubleTapAction: 'zoom',
            bgClickAction: 'close',
            paddingFn: (viewportSize) => {
                const small = viewportSize.x < 640;

                return { top: small ? 56 : 64, bottom: small ? 88 : 104, left: small ? 0 : 24, right: small ? 0 : 24 };
            },

            closeTitle: 'Fechar (Esc)',
            zoomTitle: 'Ampliar / reduzir',
            arrowPrevTitle: 'Anterior (seta para a esquerda)',
            arrowNextTitle: 'Próximo (seta para a direita)',
            errorMsg: 'Não foi possível carregar esta imagem.',
            indexIndicatorSep: ' / ',
        });

        // Após arrastar o slideshow, o clique não deve abrir o lightbox.
        lightbox.addFilter('clickedIndex', (index) => (this.mainSwiper && !this.mainSwiper.allowClick ? -1 : index));

        // Dados extras de cada item (legenda, miniatura, YouTube).
        lightbox.addFilter('domItemData', (itemData, element, linkEl) => {
            const data = { ...itemData };
            const image = linkEl.querySelector('img');

            data.caption = linkEl.dataset.caption || '';
            data.alt = data.caption || image?.alt || '';
            // A imagem já exibida no slideshow (ou a miniatura) serve de placeholder enquanto a grande carrega.
            data.msrc = image?.currentSrc || linkEl.dataset.thumb || data.msrc;

            if (linkEl.dataset.pswpType === 'youtube') {
                data.type = 'youtube';
                data.youtubeUrl = linkEl.dataset.youtubeUrl;
                data.embedUrl = linkEl.dataset.embedUrl;
                data.poster = linkEl.dataset.poster || linkEl.dataset.thumb;
            }

            return data;
        });

        // Placeholder em TODOS os slides (o padrão do PhotoSwipe usa só no primeiro).
        lightbox.addFilter('placeholderSrc', (placeholderSrc, content) => content.data.msrc || placeholderSrc);

        // Conteúdo de vídeo: pôster até o slide ficar ativo; aí entra o iframe.
        lightbox.on('contentLoad', (event) => {
            const { content } = event;
            if (content.data.type !== 'youtube') {
                return;
            }

            event.preventDefault();
            const wrapper = document.createElement('div');
            wrapper.className = 'pswp-video';
            if (content.data.poster) {
                wrapper.style.backgroundImage = `url("${content.data.poster}")`;
            }
            content.element = wrapper;
        });

        lightbox.on('contentActivate', ({ content }) => {
            if (content.data.type === 'youtube') {
                this.mountVideo(content);
            }
        });

        lightbox.on('contentDeactivate', ({ content }) => {
            if (content.data.type === 'youtube') {
                this.unmountVideo(content);
            }
        });

        lightbox.on('contentDestroy', ({ content }) => {
            if (content.data.type === 'youtube') {
                this.unmountVideo(content);
            }
        });

        lightbox.on('close', () => {
            // Garante que nenhum vídeo continue tocando depois de fechar.
            lightbox.pswp?.element?.querySelectorAll('.pswp-video iframe').forEach((iframe) => iframe.remove());
        });

        lightbox.on('uiRegister', () => this.registerLightboxUi(lightbox.pswp));

        // Mantém o slideshow sincronizado com o item visto no lightbox.
        lightbox.on('change', () => {
            if (lightbox.pswp && this.mainSwiper) {
                this.mainSwiper.slideTo(lightbox.pswp.currIndex, 0);
            }
        });

        lightbox.on('beforeOpen', () => this.mainSwiper?.keyboard?.disable());
        lightbox.on('destroy', () => {
            clearTimeout(this.clickTimer);
            this.lastClick = null;
            this.mainSwiper?.keyboard?.enable();
        });

        lightbox.init();
        this.lightbox = lightbox;
    }

    /**
     * Clique simples não faz nada (evita zoom acidental); dois cliques rápidos ampliam/reduzem no ponto clicado.
     */
    handleImageClick(point) {
        const now = Date.now();
        const last = this.lastClick;

        if (last && now - last.time < 350 && Math.abs(point.x - last.x) < 24 && Math.abs(point.y - last.y) < 24) {
            this.lastClick = null;
            this.lightbox.pswp?.currSlide?.toggleZoom({ x: point.x, y: point.y });

            return;
        }

        this.lastClick = { time: now, x: point.x, y: point.y };
    }

    mountVideo(content) {
        const wrapper = content.element;
        if (!wrapper || wrapper.querySelector('iframe')) {
            return;
        }

        const spinner = document.createElement('span');
        spinner.className = 'pswp-video__loading';
        spinner.setAttribute('aria-hidden', 'true');

        const iframe = document.createElement('iframe');
        const params = new URLSearchParams({ autoplay: '1', rel: '0', playsinline: '1', modestbranding: '1' });
        iframe.src = `${content.data.embedUrl}?${params.toString()}`;
        iframe.title = content.data.caption ? `Vídeo: ${content.data.caption}` : 'Vídeo do YouTube';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen';
        iframe.allowFullscreen = true;
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        iframe.addEventListener('load', () => spinner.remove(), { once: true });

        wrapper.append(spinner, iframe);
    }

    unmountVideo(content) {
        content.element?.querySelectorAll('iframe, .pswp-video__loading').forEach((node) => node.remove());
    }

    registerLightboxUi(pswp) {
        const currentData = () => pswp.currSlide?.data || pswp.getItemData(pswp.currIndex);

        pswp.ui.registerElement({
            name: 'download-button',
            order: 8,
            isButton: true,
            tagName: 'a',
            title: 'Baixar imagem em alta resolução',
            html: { isCustomSVG: true, inner: DOWNLOAD_ICON, outlineID: 'pswp__icn-download' },
            onInit: (element) => {
                element.setAttribute('target', '_blank');
                element.setAttribute('rel', 'noopener');

                const update = () => {
                    const data = currentData();
                    const isVideo = data?.type === 'youtube';
                    element.classList.toggle('is-hidden', isVideo || !data?.src);
                    if (!isVideo && data?.src) {
                        element.href = data.src;
                        element.setAttribute('download', '');
                    }
                };

                pswp.on('change', update);
                update();
            },
        });

        pswp.ui.registerElement({
            name: 'youtube-button',
            order: 8,
            isButton: true,
            tagName: 'a',
            title: 'Assistir no YouTube',
            html: `${YOUTUBE_ICON}<span>Assistir no YouTube</span>`,
            onInit: (element) => {
                element.setAttribute('target', '_blank');
                element.setAttribute('rel', 'noopener noreferrer');

                const update = () => {
                    const data = currentData();
                    const isVideo = data?.type === 'youtube';
                    element.classList.toggle('is-hidden', !isVideo);
                    if (isVideo) {
                        element.href = data.youtubeUrl;
                    }
                };

                pswp.on('change', update);
                update();
            },
        });

        pswp.ui.registerElement({
            name: 'custom-caption',
            order: 9,
            isButton: false,
            appendTo: 'root',
            html: '',
            onInit: (element) => {
                element.setAttribute('aria-live', 'polite');

                const update = () => {
                    element.textContent = currentData()?.caption || '';
                };

                pswp.on('change', update);
                update();
            },
        });
    }
}
