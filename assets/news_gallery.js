/*
 * Entrypoint da galeria pública das notícias (somente na página da notícia com galeria).
 *
 * - Os estilos entram no <head> via importmap(['app', 'news_gallery']), evitando "pulo" de layout.
 * - As bibliotecas são pré-carregadas (modulepreload) para o controller Stimulus "news-gallery",
 *   que é carregado sob demanda (assets/controllers/news_gallery_controller.js).
 */
import 'swiper/swiper-bundle.min.css';
import 'photoswipe/dist/photoswipe.min.css';
import './styles/news_gallery.css';

import 'swiper';
import 'swiper/modules';
import 'photoswipe/lightbox';
