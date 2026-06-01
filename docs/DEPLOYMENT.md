# Deployment checklist

## Runtime requirements

- PHP **8.1+**, WordPress **6.4+**
- Plugins: **Advanced Custom Fields Pro**, **WP BladeOne**
- Node **≥20.19** (see `package.json` / `.nvmrc`) for building assets

## Build once per release

From the theme directory:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
```

## What to ship

- **`vendor/`** from Composer (production: `--no-dev`)
- **Compiled assets** from `npm run build`:
  - Primary: `dist/css/app.css`, `dist/js/app.js` (gitignored)
  - Mirrors: `css/app.css`, `js/app.js`, root **`app.css`** (CSS committed for no-Node installs)
- Do **not** deploy `node_modules/`

Enqueue order: `app/Assets/FrontendAssets.php` prefers `dist/`, then `css/`/`js/`, then root `app.css`.

## Configuration

- **`CULVERS_USE_VITE`** — checked in `app/Assets/FrontendAssets.php` for Vite HMR during `npm run dev`
- Sync ACF field groups via **`acf-json/`** between environments

## 20i staging / live (Culver Square)

Full playbook: workspace `.cursor/rules/culvers-20i-deploy.mdc` and `society-deploy` repo.

- Staging: `https://culversquare-co-uk.stackstaging.com/`
- Theme-only deploy: rsync `wp-content/themes/culvers/` — no DB/uploads unless explicitly requested
- Remote WP-CLI: `php83 /usr/local/bin/wp` (PHP 8.0 default wp-cli breaks on theme syntax)
- After deploy: clear Blade cache `storage/cache/views/*`, purge StackCDN if HTML looks stale
- Component registry cache auto-clears when `app/Components/*.php` (or registry PHP) changes; optional `wp cache flush` on persistent object cache hosts

## Verification before merge

```bash
npm run verify
```

Includes: ESLint, Prettier, build, PHPCS, PHPStan, `check:blade-forks`.

GitHub Actions (`.github/workflows/verify.yml`): audits + verify when enabled.
