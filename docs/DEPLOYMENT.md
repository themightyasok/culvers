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

## What to ship

- **`vendor/`** from Composer when the theme relies on autoloaded PHP (production often uses `--no-dev`; keep dev tools only where you run Pint/PHPStan).
- **Compiled front-end assets** from `npm run build`:
  - **Primary:** `dist/css/app.css`, `dist/js/app.js` (created locally; **`dist/`** is gitignored).
  - **Mirrors:** same build copies CSS to **`app.css`** and **`css/app.css`**, and JS to **`js/app.js`**. WordPress loads **`dist/`** first when present, otherwise those paths (`app/setup.php`).
  - Commit **`app.css`** at the theme root so installs without running Node still get styles.
- Do **not** deploy **`node_modules/`**.

More detail on the toolchain lives in [docs/README.md](README.md).

## Configuration

- Define **`CULVERS_USE_VITE`** only where you run **`npm run dev`** with Vite HMR (`app/setup.php`).
- Sync ACF field groups via **`acf-json/`** between environments.

## Verification before merge

```bash
npm run verify
```

That command includes PHPStan (`composer analyse`). GitHub Actions runs **`npm audit`**, **`composer audit`**, then **`npm run verify`** on push/PR when enabled.
