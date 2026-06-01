# Component authoring — canonical contract

Every flexible-content layout in this theme follows one pattern. Source of truth is always code — not older docs.

| Read first | Path |
| --- | --- |
| Registry + tabs | `app/ComponentRegistry.php` |
| Per–post-type layout allowlists | `app/Config/ComponentPostTypes.php` |
| Minimal example | `app/Components/content_section.php` |
| Complex example | `app/Components/three_card_block.php` |
| Blade renderer | `resources/views/partials/flexible-components.blade.php` |
| Layout helpers | `app/Helpers/Component.php`, `LayoutShell.php`, `Rhythm.php` |
| Per-layout docs | `docs/components/` |

---

## 1. File layout

| Concern | Path | Naming |
| --- | --- | --- |
| ACF config | `app/Components/<layout_key>.php` | `snake_case` |
| Blade | `resources/views/components/<layout-key-kebab>.blade.php` | kebab-case |
| CSS (optional) | `resources/styles/components/<layout-key-kebab>.css` | import from `resources/styles/app.css` |
| Alpine (optional) | `resources/scripts/alpine/<name>.js` | register in `resources/scripts/app.js` before `Alpine.start()` |
| Doc (optional) | `docs/components/<LAYOUT-KEY-UPPER>.md` | catalogue entry |

`ComponentRegistry` **auto-discovers** `app/Components/*.php`. There is no manual registry list.

Blade resolution: `shop_split_highlight` → `shop-split-highlight.blade.php` via `App\Services\TemplateResolver`.

Shared partial (not an ACF layout): `resources/views/components/button.blade.php`.

---

## 2. ACF field model — section keys

Each component file returns an array. **Do not** declare ACF tabs in the component file — the registry injects them.

### Required top-level keys

```php
return [
    'label' => __('My block', 'culvers'),
    'display' => 'block', // optional; defaults to block
    'main' => [ /* fields */ ],
    // optional:
    'typography' => [ /* fields */ ],
    'items' => [ /* repeater fields */ ],
    'mobile' => [ /* *_mobile overrides */ ],
    'main_label' => __('Content', 'culvers'), // optional section heading in Main tab
];
```

### Editor tabs (injected by registry)

| Tab | When shown | Contents |
| --- | --- | --- |
| **Main** | Always | Theme note (grid/surface fixed in code) + `main` fields |
| **Typography** | Only if `typography` section non-empty | Per-layout type/colour overrides |
| **Items** | Only if `items` section non-empty | Repeaters, slides, cards, etc. |
| **Mobile** | Only if `mobile` section non-empty | Overrides below `md` (768px) |

There are **no** Layout & background, Visibility, or Breakpoints tabs. Grid span, band colour, and default body tone are **code-authoritative** via `App\Helpers\ComponentLayoutChrome`.

### Field shape

Each field under a section uses ACF Builder shape:

```php
'field_name' => [
    'type' => 'text',
    'options' => [
        'label' => __('Heading', 'culvers'),
        'required' => 0,
        'wrapper' => ['width' => '70'],
    ],
],
```

Use helpers from `App\Helpers\Component` for heading levels, colour pickers, button fields, etc.

### Responsive overrides

- Base fields apply from **`md` upward** (tablet + desktop share values).
- Optional `{field}_mobile` siblings override **only below `md`**.
- Mobile overrides can live in the registry **Mobile** tab (`mobile` section) or on repeater rows under **Items**.

### Per–post-type layout allowlists

`ComponentPostTypes` controls which layouts appear in **Page Components** for each post type. `ComponentRegistry::registerFlexibleContentGroups()` registers **one ACF field group per allowlist** (pages, shops, eat & drink, events, offers/news, careers).

When adding a layout:

1. Create `app/Components/{layout}.php` + Blade view.
2. Add the layout key to **exactly one** allowlist in `ComponentPostTypes.php`.
3. Run `npm run verify` — unassigned layouts fail `assertAllComponentsAssigned()`.

Directory-only layouts (`career_detail`, `shop_store_details`, `event_meta`, …) must **not** appear on generic pages. See `docs/DIRECTORY-POST-TYPES.md`.

---

## 3. Blade template contract

Every flexible component Blade should:

1. **Normalize input** — `$c = is_array($component ?? null) ? $component : [];`
2. **Root classes** — `$root = Component::rootClasses($c);` (signature: `rootClasses(array $component, bool $stripGutters = true)`)
3. **Outer section** — `<section class="… {{ $root }}" data-component-root>`
4. **Inner shell** — use `LayoutShell::INNER_MAX_GUTTERED` for all page body content (flexible blocks, directory archives, search). Site chrome (header/footer bands) may use wider outer gutters where documented in `LayoutShell.php`.
5. **Empty state** — when `$hasContent` is false and `current_user_can('edit_posts')`, `@include('partials.component-editor-placeholder')` (placeholder is currently a no-op stub — restore or remove includes project-wide)
6. **Sanitized output** — flexible rows are sanitized in `flexible-components.blade.php` via `App\Helpers\Sanitizer::component()`. WYSIWYG fields listed in `Sanitizer::wysiwygFieldKeys()` keep HTML; use `{!! wp_kses_post($html) !!}` or `TextFormatter::rich()` as appropriate

### Vertical rhythm

Parent grid (`flexible-components.blade.php`) uses `gap-y-[100px]`. Components must **not** add outer `py-*` for section spacing — that doubles rhythm.

`App\Helpers\Rhythm::spaceAboveClass()` may apply negative top margins for exceptions (flush hero → intro, slim section header → next block, etc.). See `app/Helpers/Rhythm.php`.

### Deep-link anchors

For mega-menu / header utility links, add stable `id` attributes using `App\Support\PageSectionAnchor::fromHeading($heading)` and `PageSectionAnchor::scrollMarginClass()` where sections need in-page targets.

---

## 4. Typography & tokens

- Design tokens: `resources/styles/theme.tokens.css` (`@theme`)
- Figma mapping: `docs/TYPOGRAPHY-SCALE.md`
- Section H2 default: `Component::sectionHeadingClasses()` → `text-6xl md:text-7xl` (58→64px)
- Eyebrows / labels: `font-label` (Commuters Sans)
- Body: `font-sans` (Halyard Display)

Use `Component::sectionIntroHeadingClasses()`, `sectionHeadingSpacingClasses()`, `sectionBodyToFollowContentGapClasses()`, etc. — do not invent one-off spacing class strings when a helper exists.

---

## 5. Styling — Tailwind first

- Prefer Tailwind utilities + `@theme` tokens in Blade.
- Add `resources/styles/components/*.css` only when Tailwind cannot express geometry (pseudo-elements, complex stacking, Splide overrides, scroller strip math).
- Project utilities in `tailwind.config.js` / unlayered `app.css`: `.btn`, `.culvers-focus-ring-*`, `.culvers-breakout-x`, `.rt-link-*`.
- WordPress block styles require **unlayered** button/link overrides in `app.css` — intentional cascade fight; do not duplicate without reason.

---

## 6. Alpine / JS

If the component needs interactivity:

1. Create `resources/scripts/alpine/my-component.js` exporting `registerMyComponentAlpine(Alpine)`
2. Import and call it in `resources/scripts/app.js` **before** `Alpine.start()`
3. Use `x-data="myComponent"` in Blade
4. For scroll-driven motion, wait for GSAP via `gsap:smoother:ready` or helpers in `resources/scripts/utils/`

Only **Alpine.js** for front-end interactivity — no React/Vue.

Init order in `app.js`: `Alpine.start()` → `gsapManager.init()` → `initPageHashNavigation()`. New Alpine modules must not assume `window.smoother` exists synchronously in `init()`.

---

## 7. Checklist — adding a new component

1. Add `app/Components/my_block.php` with section keys (`main` minimum).
2. Add `resources/views/components/my-block.blade.php` following §3.
3. Run `npm run verify` (lint, build, PHPCS, PHPStan, blade-fork check).
4. Add `docs/components/MY-BLOCK.md` and a row in `docs/components/README.md`.
5. Flush component cache if fields do not appear: reload admin after deploy (registry fingerprint auto-clears on `after_setup_theme`; theme switch clears cache too) or bump `ComponentCache` key version manually if needed (see `app/Services/ComponentCache.php`).
6. Optional: CSS partial + Alpine module + Playwright coverage if behaviour is non-trivial.

---

## 8. Anti-patterns

| Do not | Do instead |
| --- | --- |
| Top-level `'fields'` array in component config | Section keys `main` / `typography` / `items` / `mobile` |
| `ResponsiveFields` / per-component ACF tabs | Registry-injected tabs |
| Editor visibility / width / tone toggles | Fixed in `ComponentLayoutChrome` |
| Outer `py-24` on component roots | Trust grid `gap-y-[100px]` + `Rhythm` |
| Hard-coded hex in Blade | `@theme` tokens or `ThemeTokens` pickers |
| Inline `px-*` on `.btn` links | Use `.btn-primary` / `.btn-outline` hover widen |
| Runtime PHP “seed” arrays merged at render | Content lives in DB (ACF); CLI scripts write DB when explicitly run |

---

## 9. Registry cache & errors

- Loaded component configs are cached in **`wp_cache`** under key `culvers_theme_components_v17` (~1 hour TTL).
- **Deploy invalidation:** `ComponentCache::invalidateIfRegistrySourcesChanged()` runs on `after_setup_theme` — fingerprints `ComponentRegistry.php`, `ComponentPostTypes.php`, `FieldValidator.php`, and all `app/Components/*.php` mtimes; clears cache when they change. Theme activation also clears cache (`after_switch_theme`).
- **Registry load errors** persist to `culvers_component_load_errors` and show an admin notice.
- **ACF bootstrap errors** (`Fields` constructor) persist to `culvers_acf_bootstrap_errors` and show an admin notice — always logged, not gated on `WP_DEBUG`.
- Manual reset: bump `CACHE_KEY` version in `ComponentCache.php` or `wp cache flush`.

---

## 10. Further reading

- Editors: `docs/EDITOR-FLEXIBLE-CONTENT.md`
- Directory CPTs on singles/archives: `docs/DIRECTORY-POST-TYPES.md`
- GSAP / scroll: `docs/GSAP.md`
- CLI populate scripts: `docs/SCRIPTS.md`
