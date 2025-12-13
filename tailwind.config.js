/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
    "./node_modules/flowbite/**/*.js"
  ],
  theme: {
    container: {
      center: true,
      padding: '1rem',
      screens: {
        sm: '640px',
        md: '768px',
        lg: '1024px',
        xl: '1280px',
        '2xl': '1320px', // Standard Bootstrap/Joomla width
      },
    },
    extend: {
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        display: ['Plus Jakarta Sans', 'sans-serif'],
      },
      colors: {
        primary: {
          50: '#f0fbfc',
          100: '#d6f4f8',
          200: '#afe9f1',
          300: '#7ad8e6',
          400: '#3ebed6',
          500: '#00A3C8', // Reference Base Color
          600: '#0084a8',
          700: '#006a88',
          800: '#005972',
          900: '#00495d', // Dark Medical Blue
        },
      }
    },
  },
  plugins: [
    require('flowbite/plugin'),
    require('@tailwindcss/typography'),
  ],
}
