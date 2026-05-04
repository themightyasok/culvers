import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './app/**/*.php',
    './resources/scripts/**/*.{js,mjs}',
  ],
  plugins: [typography],
};
