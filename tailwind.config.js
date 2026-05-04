import typography from '@tailwindcss/typography';
import plugin from 'tailwindcss/plugin';

/**
 * Culver Square theme hooks:
 * - `addBase`: global anchors + keyboard focus (no per-link utilities in Blade/PHP).
 * - `addComponents`: footer link treatments (typography + hover colour in one place).
 * Prose links: `theme.extend.typography`.
 *
 * Persistent underline exceptions stay in app.css (`.footer-link--persistent-underline`).
 */
const culversThemePlugin = plugin(({ addBase, addComponents }) => {
  addBase({
    'a:not(.footer-link--persistent-underline)': {
      textDecorationLine: 'none',
      textDecorationThickness: 'from-font',
    },
    'a:not(.footer-link--persistent-underline):focus-visible': {
      outline: '2px solid currentColor',
      outlineOffset: '3px',
      borderRadius: '2px',
    },
  });

  addComponents({
    '.footer-nav__link': {
      '@apply font-sans text-sm leading-snug text-light-cream/85 transition-colors hover:text-glowleaf':
        {},
    },
    '.footer-nav__link--legal': {
      '@apply font-sans text-micro uppercase tracking-label text-lighter-cream transition-colors hover:text-glowleaf':
        {},
    },
    '.footer-nav__link-phone': {
      '@apply font-sans text-sm text-light-cream/85 transition-colors hover:text-glowleaf': {},
    },
    '.footer-nav__link-social': {
      '@apply inline-flex items-center gap-2 font-sans text-xs font-semibold uppercase tracking-label text-light-cream/90 transition-colors hover:text-glowleaf':
        {},
    },
  });
});

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './app/**/*.php',
    './resources/scripts/**/*.{js,mjs}',
  ],
  theme: {
    extend: {
      typography: () => ({
        DEFAULT: {
          css: {
            a: {
              textDecoration: 'none',
            },
            'a:hover': {
              textDecoration: 'none',
            },
          },
        },
      }),
    },
  },
  plugins: [culversThemePlugin, typography],
};
