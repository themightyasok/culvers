# Deployment checklist

## Runtime requirements

- PHP **8.1+**, WordPress **6.4+**
- Plugins: **WP BladeOne**, **ACF Pro** (flexible content)
- Node **≥20.19** (see `package.json` `engines` and `.nvmrc`) for building assets

## Build once per release

From the theme directory:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
```

Commit **or** deploy artifacts:

- `vendor/` from Composer (production install uses `--no-dev` unless you need Pint/PHPStan on the server)
- `css/app.css`, `js/app.js`, and root `app.css` from `npm run build`
- Do **not** deploy `node_modules/`

## Configuration

- Define `CULVERS_USE_VITE` only in local/staging when running `npm run dev` for HMR (see `app/setup.php`).
- Sync ACF field groups via `acf-json/` when moving between environments.

## Verification before merge

```bash
npm run verify
composer analyse
```

CI runs these on push/PR when GitHub Actions is enabled.
