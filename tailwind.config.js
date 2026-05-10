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
    '.footer-nav__link': {
      '@apply font-sans text-sm leading-snug text-light-cream/85 transition-colors hover:text-glowleaf':
        {},
    },
    /* Column nav `<a>` styling — wp_nav_menu emits bare `<li><a>`, so we target descendants here
     * once instead of repeating `[&>li>a]:…` arbitrary variants on every menu_class string. */
    '.footer-nav--col li > a': {
      '@apply font-sans text-lg leading-7 text-light-cream transition-colors hover:text-glowleaf':
        {},
    },
    '.footer-nav__link--legal': {
      '@apply font-label text-xs uppercase tracking-widest text-lighter-cream transition-colors hover:text-glowleaf':
        {},
    },
    '.footer-nav__link-phone': {
      '@apply font-sans text-xl leading-tight text-lighter-cream transition-colors hover:text-glowleaf':
        {},
    },
    '.footer-nav__link-social': {
      '@apply inline-flex items-center gap-2 font-label text-sm font-semibold uppercase tracking-widest text-light-cream/90 transition-colors hover:text-glowleaf':
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
