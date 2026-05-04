# Culvers theme documentation

| Doc                                   | Purpose                                     |
| ------------------------------------- | ------------------------------------------- |
| [Deployment checklist](DEPLOYMENT.md) | Server requirements, build output paths, CI |
| [GSAP licensing](GSAP.md)             | ScrollSmoother / Club GSAP                  |

## Assets and Tailwind CSS v4

- **Entry stylesheet:** `resources/styles/app.css` — imports Tailwind, `@config '../../tailwind.config.js'`, tokens, and third-party CSS (e.g. Splide).
- **Design tokens:** `resources/styles/theme.tokens.css` — `@theme` for `--color-*`, `--font-*`, `--shadow-*`, etc. **`App\Config\ThemeTokens`** parses `--color-*` hex values for ACF colour pickers. **`App\Helpers\TailwindColors`** builds text/bg utility dropdown choices from the same definitions (order follows the CSS file).
- **Spacing:** Layout helpers (`Padding`, `Grid`) and Blade markup use Tailwind’s **default spacing scale** (`pt-16`, `gap-x-6`, `px-5`, …). You can still tune the global scale by defining **`--spacing-*`** in `@theme` if needed.
- **Backgrounds:** **`App\Helpers\Background`** uses **`bg-{slug}`** when a solid `#hex` matches a theme colour; otherwise it keeps inline `background-color` / gradients / media as before.
- **Templates:** Blade files live under **`resources/views/`**; Tailwind scans them via **`tailwind.config.js`** `content` paths.
- **JS bundle:** `resources/scripts/app.js` (Vite input).
- **Build:** `npm run build` writes to **`dist/css/app.css`** and **`dist/js/app.js`**, then copies into **`app.css`**, **`css/app.css`**, and **`js/app.js`**. **`dist/`** is gitignored; **`app.css`** at the theme root is committed so installs without Node still get CSS.
- **Enqueue:** `app/setup.php` prefers **`dist/`**, then **`css/app.css`**, then root **`app.css`**; scripts mirror that pattern for **`dist/js`** vs **`js/`**.
- **Site Editor:** **`theme.json`** registers semantic colours and the sans font family for WordPress (alongside Tailwind-driven front-end styles).

## Tailwind config vs tokens

- **`tailwind.config.js`** — `content` globs (Blade, PHP under `app/`, scripts under `resources/scripts`) and the **`@tailwindcss/typography`** plugin. Colour scales live in CSS `@theme`, not in this file.
- **PHP helpers** (`TailwindColors`, `Padding`, `Grid`, `Background`) map ACF fields to utility classes; see each class for specifics.

## Quality checks

- **`npm run verify`** — ESLint, Prettier check, production build, **`composer lint`** (PHPCS), **`composer analyse`** (PHPStan).
- **`composer audit`** / **`npm audit`** — run in CI (`.github/workflows/verify.yml`); run locally before release if you are not using Actions.
