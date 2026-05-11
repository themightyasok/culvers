# Editing flexible page components (ACF)

This theme builds pages from **Page Components** (ACF Flexible Content). Each row is a block with shared **Layout & background**, **Typography**, and **Visibility** tabs, then your block-specific fields (General, Structure, Items, optional typography/spacing tabs, etc.).

## Built-in UX (ACF PRO 6.5+)

These ship with ACF — no theme setup required:

- **Rename a layout instance** in the editor (friendly label only; the technical layout key does not change).
- **Disable** a layout without deleting it — data stays; it does not render on the front until re-enabled.
- **Active layout highlight** while editing fields deep inside a row.
- **Collapse / expand all** layouts for quicker reordering on long pages.

## Visibility breakpoints

Under **Visibility**:

- **Hide on phones** — below the `md` breakpoint (~768px).
- **Hide from tablet / desktop up** — `md` and wider (tablet + desktop share this band). Phones still see the block.

Per-component **Breakpoints** tab: explains md vs phones. **Mobile overrides** appears only when that block has layout-level fields; carousel-style blocks put mobile imagery on each slide under **Items**.

Use row visibility sparingly; prefer designing one responsive presentation where possible.

## After structural registry changes

Developers bump the component registry cache version when flexible fields change. If something looks missing after a deploy, run **flush caches** (WP Admin → or WP-CLI `wp cache flush`) and reload the editor.

For migrations from older sites, see:

- `scripts/migrations/2026-05-10-flexible-visibility-breakpoints.php`
- `scripts/migrations/2026-05-10-drop-tablet-visibility-meta.php`
