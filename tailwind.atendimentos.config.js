/** Mesmo tema do tailwind.config.js, lendo só as páginas de atendimentos (gera public/css/atendimentos.css). */
const base = require('./tailwind.config.js');

module.exports = {
  ...base,
  content: [
    './templates/atendimentos/**/*.html.twig',
    './templates/transparency/index.html.twig',
    './public/js/atendimentos.js',
  ],
};
