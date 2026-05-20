import typography from '@tailwindcss/typography';
import plugin from 'tailwindcss/plugin';

/**
 * Culver Square theme hooks:
 * - `addBase`: global anchors + keyboard focus (no per-link utilities in Blade/PHP).
 * - `addComponents`: footer link treatments (typography + hover colour in one place).
 * Prose links: `theme.extend.typography`.
 *
 * Persistent underline exceptions stay in app.css (`.footer-link--persistent-underline`).
 * Rich-text blocks: `rt-link-*` utilities (Figma-aligned link colours / underlines on CMS HTML).
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
    /*
     * Focus rings — one place for WCAG-visible focus styling (avoid pasting 4
     * `focus-visible:*` utilities on every interactive control). Compose with
     * `focus-visible:rounded-sm` etc. where a control isn’t already rounded.
     */
    '.culvers-focus-ring': {
      '@apply focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-glowleaf':
        {},
    },
    '.culvers-focus-ring-compact': {
      '@apply focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-glowleaf':
        {},
    },
    '.culvers-focus-ring-compact-white': {
      '@apply focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white':
        {},
    },
    '.culvers-focus-ring-compact-deep-moss': {
      '@apply focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-deep-moss':
        {},
    },
    '.culvers-focus-ring-deep-moss': {
      '@apply focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-deep-moss':
        {},
    },
    '.culvers-focus-ring-compact-faded-olive': {
      '@apply focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-faded-olive':
        {},
    },
    /*
     * Rich text (CMS WYSIWYG / prose) — anchor styling inside a block. Parent sets body colour;
     * these only touch `a` descendants (matches former `[&_a]:…` chains).
     */
    '.rt-link-prose': {
      '@apply [&_a]:text-deep-moss [&_a]:underline [&_a]:decoration-glowleaf [&_a]:underline-offset-4 hover:[&_a]:decoration-deep-moss':
        {},
    },
    '.rt-link-faded': {
      '@apply [&_a]:text-faded-olive [&_a]:underline [&_a]:underline-offset-4': {},
    },
    '.rt-link-brand': {
      '@apply [&_a]:text-brand-500 [&_a]:underline [&_a]:decoration-brand-500 [&_a]:underline-offset-4':
        {},
    },
    '.rt-link-olive-surface': {
      '@apply [&_a]:text-inherit [&_a]:underline [&_a]:decoration-glowleaf [&_a]:underline-offset-4 hover:[&_a]:opacity-90':
        {},
    },
    '.footer-sans-book': {
      fontFamily:
        "'halyard-display', 'halyard-display-extralight', ui-sans-serif, system-ui, sans-serif",
      fontWeight: '400',
      fontStyle: 'normal',
    },
    '.footer-nav__link': {
      '@apply font-sans text-base leading-snug text-light-cream/85 transition-colors hover:text-glowleaf':
        {},
    },
    /* Column nav — Figma 51:5147: Halyard Book 18px / lh 26 (desktop trim in site-footer-desktop.css). */
    '.footer-nav--col li > a.footer-nav__link-col': {
      '@apply footer-sans-book text-[18px] leading-[26px] text-white transition-colors hover:text-glowleaf':
        {},
    },
    '.footer-nav__link--legal': {
      '@apply font-label text-[10px] font-normal uppercase tracking-[0.5px] leading-[24px] text-lighter-cream transition-colors hover:text-glowleaf':
        {},
    },
    '.footer-nav__link-phone': {
      '@apply footer-sans-book text-[20px] leading-[30px] text-white transition-colors hover:text-glowleaf':
        {},
    },
    '.footer-nav__link-social': {
      '@apply inline-flex items-center gap-[7px] font-label text-[14px] font-semibold uppercase tracking-[1.165px] leading-[28px] text-light-cream/90 transition-colors hover:text-glowleaf':
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
