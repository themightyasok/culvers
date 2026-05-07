# Component authoring — the canonical contract

Every flexible-content component in this theme follows the same shape. The
goal is **one predictable pattern** so editors, reviewers, and AI agents
can read, build, modify, or replace any component without surprises.

> **AI agents, read this first.** Anything that doesn't follow the rules
> here is wrong by definition — fix the component, don't bend the rules.
> When this file disagrees with one component, the rules win and the
> component gets aligned.

> Source of truth (code):
> [`app/ComponentRegistry.php`](../app/ComponentRegistry.php),
> [`app/Helpers/Component.php`](../app/Helpers/Component.php),
> [`app/Components/`](../app/Components),
> [`resources/views/components/`](../resources/views/components).
>
> Per-component documentation:
> [`docs/components/`](components/) — one file per layout key.

---

## 1. File layout

A component lives in **two required files** plus optional CSS/JS partials
and a documentation page. Naming is mechanical:

| Concern         | Path                                                     | Naming                                |
| --------------- | -------------------------------------------------------- | ------------------------------------- |
| ACF field model | `app/Components/<layout>.php`                            | snake_case                            |
| Blade template  | `resources/views/components/<layout-kebab>.blade.php`    | kebab-case = `<layout>` with hyphens  |
| (Optional) CSS  | `resources/styles/components/<layout-kebab>.css`         | kebab-case BEM root file              |
| (Optional) JS   | `resources/scripts/alpine/<layout-kebab>.js`             | kebab-case Alpine module              |
| Documentation   | `docs/components/<LAYOUT-KEBAB-UPPER>.md`                | UPPER-KEBAB to match other docs       |

`ComponentRegistry::loadFromFiles()` discovers `app/Components/*.php`
automatically — **there is no registry list to update**. Drop the file in,
flush the WordPress cache, and the layout appears in the Page Components
flexible content field.

The Blade is wired by `partials/flexible-components.blade.php` via
`TemplateResolver` — again no manual mapping. The kebab-case filename **must
match the layout key** exactly: `shop_split_highlight` →
`shop-split-highlight.blade.php`.

---

## 2. ACF field model — `app/Components/<layout>.php`

### Required scaffold

```php
<?php

/**
 * <One-paragraph summary: what this component renders, when to reach for
 * it, what it does NOT do (so future authors don't reinvent a sibling).>
 *
 * Figma ref: <node id> when applicable.
 */

use App\Helpers\Component;

return [
    'label' => __('<Editor-facing label>', 'culvers'),
    'display' => 'block',
    'fields' => [
        'tab_general' => [
            'type' => 'tab',
            'options' => ['label' => __('Content', 'culvers')],
        ],

        // Component-specific fields go here. Always prefix with the
        // layout subject (see "Naming rules" below).

        'tab_padding' => [
            'type' => 'tab',
            'options' => ['label' => __('Padding', 'culvers')],
        ],
    ],
];
```

### What the registry adds for you (do NOT redefine these)

`ComponentRegistry::addGeneralTab()` injects these on **every** layout
under the General tab — re-defining them in your component file just
duplicates the field and breaks ACF:

| Field key                       | What it controls                                                                                  |
| ------------------------------- | ------------------------------------------------------------------------------------------------- |
| `component_width`               | 6–12 column span (`Grid::getColumnChoices()`).                                                    |
| `background_type` + descendants | None / colour / gradient / image / image-centred / video. Read via `Background::process($c)`.     |
| `background_overlay_*`          | Overlay colour & opacity for image/video backgrounds.                                             |
| `background_parallax`           | Subtle scroll parallax (desktop only).                                                            |
| `body_text_tone`                | Default body copy colour. Read via `Component::bodyTextTone($c[, 'light-band'])`.                 |
| `visibility_mobile`             | `visible` / `hidden`. Hides at <768px via `culvers-hide-below-md`.                                |

`addPaddingTab()` adds `top_padding` + `bottom_padding`. The Blade picks
them up via `Component::rootClasses($c)` — no manual `pt-*` / `pb-*`.

### Naming rules

- **Top-level fields** are prefixed with the layout subject — short,
  matches the spirit of the layout key. Examples from the codebase:

  | Layout key             | Prefix    | Example fields                              |
  | ---------------------- | --------- | ------------------------------------------- |
  | `info_block`           | `info_`   | `info_heading`, `info_items`, `info_cta_*`  |
  | `three_card_block`     | `cards_`  | `cards_source`, `cards_items`, `cards_*`    |
  | `shop_split_highlight` | `split_`  | `split_ratio`, `split_kicker`, `split_*`    |
  | `horizontal_scroller`  | `scroller_` | `scroller_items`, `scroller_speed`, …      |
  | `event_meta`           | `event_meta_` | `event_meta_date_value`, `event_meta_cta_*` |

  **Every** field for the layout uses the same prefix. No exceptions.

- **Repeater sub-fields** drop the layout prefix and use a **singular subject**:
  `item_*`, `card_*`, `slide_*`, `tab_*`, `pin_*`. Inside the repeater
  the prefix is implied by the parent.

- **Heading-level selects** use the canonical helper:
  `'<prefix>_heading_level' => Component::headingLevelField()`.
  Pass `allowH1: true` on layouts that may legitimately host the page H1
  (`content_section`, `three_card_block`, `image_hero`, …). Pass
  `default: 1` for components whose default heading is the H1
  (`career_detail` job title).

- **WYSIWYG fields** order their options as
  `label → instructions → required → default_value → tabs → toolbar → media_upload`.
  Use `'toolbar' => 'full'` + `'media_upload' => 1` for long-form rich
  copy; `'basic'` + `0` for tight design copy (panel bodies, FAQ answers).

- All editor-facing strings go through `__('…', 'culvers')`.

- Tabs always exist as the **first** key (`tab_general`) and the **last**
  key (`tab_padding`). For multi-section layouts add intermediate tabs
  in between (`tab_role`, `tab_fonts`, `tab_padding`) — see
  `career_detail` and `horizontal_scroller`.

- Field width hints use the standard `'wrapper' => ['width' => '50']`
  (or `'33'` / `'40'` / `'60'`) to lay out paired inputs side-by-side in
  the editor — match the visual pairing in the design.

### Migrations

When you change a field key on an existing layout, write a one-shot
migration under `scripts/migrations/<date>-<purpose>.php` and run it via
`scripts/with-local-env.sh wp eval-file …`. Existing migration files are
the template. Never silently break saved data.

---

## 3. Blade template — `resources/views/components/<layout-kebab>.blade.php`

### Required scaffold

```blade
@php
  use App\Helpers\Component;
  use App\Helpers\LayoutShell;
  // Other helpers (Image, ThreeCardBlock, …) only as needed.

  /**
   * <One-paragraph what this renders + Figma ref when useful.>
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $tone = Component::bodyTextTone($c); // pass 'light-band' on white/cream bands

  // Resolve every editor field into a local variable (trimmed, type-narrowed).
  $heading = trim((string) ($c['<prefix>_heading'] ?? ''));
  $headingTag = Component::headingTag($c['<prefix>_heading_level'] ?? null);

  // Compute a single $hasContent boolean covering every field that could
  // make this component meaningful. Used by the empty-state branch below.
  $hasContent = $heading !== '' || /* … other field checks … */ false;
@endphp

@if(! $hasContent)
  @if(current_user_can('edit_posts'))
    @include('partials.component-editor-placeholder', [
        'wrapperClasses' => $root,
        'message' => __('<editor hint>', 'culvers'),
    ])
  @endif
@else
  <section
    class="<bem-root> {{ esc_attr($root) }} <design-utility-classes>"
    data-component-root
    data-<bem-root>>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      {{-- markup --}}
    </div>
  </section>
@endif
```

### Mandatory invariants

| Rule | Why |
| ---- | --- |
| `$c = is_array($component ?? null) ? $component : [];` | Defensive coercion — `$component` may be missing or non-array on broken data. |
| `$root = Component::rootClasses($c);` | Single source of truth for `_grid_classes` + padding. Strips inset gutters when the component renders its own inner shell. |
| `$tone = Component::bodyTextTone($c[, 'light-band'])` | Sanitises the editor-picked tone; `'light-band'` defends white/cream bands against unreadable picks. |
| `$headingTag = Component::headingTag($c['<prefix>_heading_level'] ?? null);` | Clamps the editor value to `h1`–`h6`. |
| `<section class="<bem-root> {{ esc_attr($root) }} …" data-component-root data-<bem-root>>` | Every component's outermost element MUST carry its BEM root, the `data-component-root` marker, and a `data-<bem-root>` selector hook for JS / tests. |
| Empty-state branch with `current_user_can('edit_posts')` + `partials.component-editor-placeholder` | Logged-in editors get a clear empty-state hint on every component — never a silent blank gap. |
| Use `LayoutShell::INNER_MAX_GUTTERED` | Site-shell gutter (`mx-auto w-full max-w-8xl px-4 md:px-12`). Components inherit horizontal alignment with the header & footer. |
| Escape every editor string at the boundary | `esc_html`, `esc_attr`, `esc_url`. Use `wp_kses_post(wpautop(...))` for trusted WYSIWYG output. |

### Padding & grid

`Component::rootClasses($c)` calls `Grid::stripHorizontalInsetPadding()`
by default. That is the right behaviour for any component that renders
its own inner shell (`LayoutShell::INNER_*`). For a component that
intentionally inherits the main grid gutters as its frame
(e.g. `content-section`), opt out:

```php
$root = Component::rootClasses($c, stripGutters: false);
```

For full-bleed components (hero shells, `image_hero`, `hero_slider`)
that must paint edge-to-edge with no `pt-*`/`pb-*` band, opt out of
padding:

```php
$root = Component::rootClasses($c, includePadding: false);
```

These choices are well-documented in their existing components — copy
the pattern, don't invent new control surfaces.

### Heading-level wiring

```php
$headingTag = Component::headingTag($c['<prefix>_heading_level'] ?? null);
```

Then in the markup:

```blade
<{{ $headingTag }} class="font-heading text-4xl leading-tight md:text-5xl lg:text-6xl">
  {{ esc_html($heading) }}
</{{ $headingTag }}>
```

The helper clamps anything outside `1..6` back to the registered default.

---

## 4. Tailwind & token rules

This theme is **Tailwind-first**. Custom classes only exist when there's
an actual reason. Read [`docs/TYPOGRAPHY-SCALE.md`](TYPOGRAPHY-SCALE.md)
before touching font sizes.

### Do

- Pick **one** stock `text-*` utility per element. Pair with stock
  `tracking-*` / `leading-*` utilities, or arbitrary values when Figma
  sits between defaults (`tracking-[0.22em]`, `leading-[26px]`).
- Use the design tokens defined in `resources/styles/theme.tokens.css`:
  Tailwind colour utilities (`text-deep-moss`, `bg-glowleaf`,
  `border-faded-olive`, `bg-brand-500`) generated from `--color-*`.
- Use `LayoutShell::INNER_MAX_GUTTERED` for inner widths so every
  component aligns horizontally with the header & footer.
- Numerical pattern extensions are allowed where Tailwind's own scale
  stops short: `max-w-8xl` (90 rem shell), `z-60`/`z-70`/`z-80` (header
  stack). These are the only invented utilities.

### Do NOT

- Do NOT invent semantic classes (`.text-display-md`, `.text-caption`,
  `.text-prose-md`, `.text-hero-fluid`). Snap close-fit Figma values
  onto the existing scale; the four documented snap losses in
  TYPOGRAPHY-SCALE.md are the only intentional ≤ 2 px deviations.
- Do NOT use raw arbitrary `text-[Npx]` when a stock utility gets within
  ≤ 2 px of the Figma value.
- Do NOT add CSS for things Tailwind already does (`.flex`, `.grid`,
  `.mt-4`, …). Only reach for a CSS partial when you need
  multi-selector styling or animations Tailwind cannot express.
- Do NOT hand-write `<img>` tags. Use `Image::render($acfImage, $args)`
  so width/height/loading/decoding/fetchpriority defaults stay
  consistent. (Inline `<img>` in editor-controlled HTML inside a
  WYSIWYG is fine — that's not a component author writing markup.)

---

## 5. CSS — BEM partials per component (only when needed)

A component CSS file only exists when the component genuinely needs:

- JS-readable class hooks (cloned items, modifier-driven layouts).
- Selectors that Tailwind utilities cannot express (descendant
  selectors, reduced-motion media queries that target child elements,
  multi-stop CSS gradients, complex `@keyframes`).
- Variants tied to component state (`.centre-map__pin--active` driven
  by Alpine `activeSlug`).

Rules:

- **One BEM root per component file**; root matches the Blade
  `<section>`'s first class. Examples: `.content-section`,
  `.content-section__heading`, `.centre-map`, `.centre-map__pin`.
- Repeating items that have their own elements (`__media`, `__copy`, …)
  live as a **sibling block**: `.horizontal-scroller-item` rather than
  nesting two `__` levels under the parent.
- Modifiers attach to the root: `.horizontal-scroller--disable-scroll`,
  `.centre-map__pin--active`.
- Inside a component CSS file prefer `@apply` with Tailwind utilities
  so design tokens stay the source of truth; only drop to raw CSS when
  a utility doesn't exist (custom keyframes, viewport-aware gradients).
- Add the `@import './components/<file>.css';` line to
  `resources/styles/app.css` so it ships in the bundle.

---

## 6. JS — Alpine modules per component (only when needed)

If the component needs interactivity, add an Alpine factory.

- **Location**: `resources/scripts/alpine/<layout-kebab>.js`.
- **Shape**:

  ```js
  /**
   * <one-line summary>
   *
   * @param {import('alpinejs').Alpine} Alpine
   */
  export default function register<PascalName>Alpine(Alpine) {
    Alpine.data('<camelName>', () => ({
      // state
      foo: '',
      // methods
      doThing() { … },
    }));
  }
  ```

- **Register** it from `resources/scripts/app.js`, *before* `Alpine.start()`.
- **Wire** the Blade root with `data-<bem-root>` *and* `x-data="<camelName>"`.
- **Read configuration from `data-*` attributes** set in the Blade —
  never reach back into the ACF payload from JS. Every value the
  module needs is rendered into the markup at request time.
- **Class manipulation in JS uses BEM modifiers**, not raw utility
  classes (`el.classList.toggle('foo--open')`, never
  `el.classList.add('hidden')`).
- **Reduced motion**: respect `prefers-reduced-motion: reduce` for any
  auto-running animation. One-shot init checks
  (`window.matchMedia('(prefers-reduced-motion: reduce)').matches`)
  are sufficient when behaviour is set once at load. Long-lived
  managers in `resources/scripts/utils/` subscribe to the media query
  and re-tear down on toggle.
- **No global state**. Each Alpine component is self-contained; sharing
  goes through DOM events or the existing utility singletons in
  `resources/scripts/utils/`.

---

## 7. Helper reference (high-traffic)

| Helper                                       | Purpose                                                                  |
| -------------------------------------------- | ------------------------------------------------------------------------ |
| `Component::rootClasses($c, $stripGutters, $includePadding)` | Section opener classes (grid + padding) — the canonical wrapper string. |
| `Component::headingTag($value, $default = 2)` | Clamp editor heading-level pick to a valid `h1`–`h6` tag.                |
| `Component::headingLevelField($instr, $allowH1, $default)` | Drop-in ACF field config for a heading-level select.                  |
| `Component::bodyTextTone($c, $variant)`      | Sanitise body-text tone; `'light-band'` defends white/cream bands.       |
| `Padding::getClasses($c)`                    | Translate ACF padding choices to Tailwind `pt-*` / `pb-*`.               |
| `Grid::stripHorizontalInsetPadding($s)`     | Remove inherited inset padding when the component renders its own shell. |
| `Background::process($c)`                    | Resolve the General-tab background fields into classes/styles/media.     |
| `LayoutShell::INNER_MAX_GUTTERED`            | Site-shell inner width (`mx-auto w-full max-w-8xl px-4 md:px-12`). Aligns with header + footer. |
| `LayoutShell::INNER_MAX_FLUSH_X`             | Same width with no horizontal padding (grids that own their own inset). |
| `LayoutShell::INNER_READABLE_960`            | Narrow readable column (~960 px) for hours / store details. |
| `TailwindColors::sanitizeBodyTextTone($v)`   | Allowed body-text tones (called by `Component::bodyTextTone`).           |
| `Image::render($image, $args)`               | Canonical `<img>` renderer. Components MUST go through this helper rather than hand-writing `<img>` tags. |
| `Cast::toString($mixed)` / `Cast::toInt($mixed)` | Type-safe coercion of `mixed` (WP / ACF / customizer) to scalars before further processing. Use at the WordPress boundary so the rest of the codebase can rely on narrow types. |
| `Sanitizer::component($row)`                 | Normalises one flexible row before `flexible-components.blade.php` renders it; runs automatically — components don't call this. |

---

## 8. Components with external configuration / services

Some components need site-wide configuration that isn't editor-content
(API keys, canonical addresses, recipient emails). The pattern is the
same every time:

1. Add a `final` Customizer class under
   `app/Customizer/<Subject>Customizer.php` with `MOD_*` constants and
   static `register(\WP_Customize_Manager $wp)` plus typed accessors.
2. Wire the registration call into `app/setup.php` inside the existing
   `customize_register` action.
3. Read it from the Blade via
   `App\Customizer\<Subject>Customizer::accessor()`.
4. Document the setup in `docs/components/<COMPONENT>.md`.

For components that talk to a third-party HTTP service:

- The HTTP wrapper lives in `app/<Subject>/` (e.g. `app/Travel/`) as a
  `final` class; uses `wp_remote_*`; caches responses via
  `set_transient`.
- A `WP_REST` endpoint proxies the call so the API key never leaves
  the server (`app/<Subject>/<Subject>Endpoint.php`). Always
  nonce-gate (`X-WP-Nonce` header,
  `wp_verify_nonce($value, 'wp_rest')`) and add a per-IP soft
  rate-limit via `set_transient`.
- The Alpine module fetches that REST endpoint with
  `credentials: 'same-origin'` and renders results into a
  `role="status" aria-live="polite"` container.
- Honeypot field for any public-facing form
  (visually-hidden input named `website` — bots fill every input).

The two reference implementations are
[`docs/components/CONTACT.md`](components/CONTACT.md) and
[`docs/components/TRAVEL-CALCULATOR.md`](components/TRAVEL-CALCULATOR.md).

---

## 9. Accessibility checklist

Every component must:

- Use semantic HTML (`<section>`, `<header>`, `<aside>`, `<dl>`,
  `<button>`, `<a>`) — never reach for a `<div>` when something more
  specific exists.
- Provide a single, predictable heading hierarchy. Use
  `Component::headingTag()`; never hard-code `<h2>`.
- Decorate icons with `aria-hidden="true"`. Provide an `aria-label` or
  visually-hidden text for icon-only controls.
- Carry visible focus states for any interactive element. Use the
  `focus-visible:outline focus-visible:outline-2
  focus-visible:outline-offset-2 focus-visible:outline-glowleaf`
  pattern (or the existing `.btn` family).
- Support keyboard interaction for any custom widget (Esc closes
  panels, arrow keys for radio groups, roving `tabindex` for tab
  panels — see `text-image-slider.js`, `faq.js`).
- Respect `prefers-reduced-motion: reduce` for any auto-running
  animation (see Section 6).
- Set ARIA attributes correctly:
  - Disclosure: `aria-expanded`, `aria-controls`, paired `id`.
  - Tab UI: `role="tablist"` / `role="tab"` / `role="tabpanel"`,
    `aria-selected`, `aria-controls`, `aria-labelledby`.
  - Live regions: `role="status" aria-live="polite"` for async
    results.

---

## 10. Documentation

**Every component file ships with a documentation page** under
`docs/components/<LAYOUT-KEBAB-UPPER>.md`. This is non-negotiable. The
page covers:

1. One-paragraph "what is it / when to use".
2. A meta table (layout key, file paths, BEM root, Figma reference,
   any external service).
3. A table of every editor field (name, type, default, notes).
4. A "Behaviour notes" section for animations, conditional rendering,
   third-party calls, accessibility quirks.
5. A "Related components" section that says **when to pick this vs. a
   sibling**. This is how editors find the right block.

[`docs/components/README.md`](components/README.md) is the catalogue
index. Add a new row when you add a component.

---

## 11. Build a new component from scratch — checklist

For an AI agent (or human) creating a brand-new component, in order:

```text
[ ] 1. Pick a layout key:           snake_case, prefix-distinct
                                    (e.g. event_meta, centre_map).
[ ] 2. Create app/Components/<layout>.php using the scaffold in §2.
       - Top docblock (what / when / Figma ref).
       - tab_general → fields → tab_padding.
       - Subject-prefix every field.
       - Use Component::headingLevelField() for any heading select.
[ ] 3. Create resources/views/components/<layout-kebab>.blade.php
       using the scaffold in §3.
       - $c coercion, rootClasses, headingTag, bodyTextTone.
       - Resolve every field into a typed local variable.
       - $hasContent gate + editor placeholder.
       - Outer <section class="<bem> {{ esc_attr($root) }}" 
         data-component-root data-<bem>>.
       - LayoutShell::INNER_MAX_GUTTERED for the inner shell.
[ ] 4. (Optional) resources/styles/components/<layout-kebab>.css
       BEM block; @apply Tailwind tokens. Add the @import to app.css.
[ ] 5. (Optional) resources/scripts/alpine/<layout-kebab>.js — see §6.
       Register in resources/scripts/app.js BEFORE Alpine.start().
[ ] 6. (External services?) See §8 — Customizer + REST wrapper.
[ ] 7. Documentation: docs/components/<LAYOUT-KEBAB-UPPER>.md.
       Add the row to docs/components/README.md.
[ ] 8. Verification:
       npm run verify
       Then flush WP cache + smoke-test the rendered page (§12).
```

If you skip step 7 the work is not done.

---

## 12. Verification

Before opening a PR for a new or modified component:

```bash
cd app/public/wp-content/themes/culvers
npm run verify          # eslint + prettier + vite build + phpcs + phpstan
```

Then smoke-test the rendered output via Local. Useful sanity checks
(run from `app/public`):

```bash
./wp-content/themes/culvers/scripts/with-local-env.sh wp cache flush
./wp-content/themes/culvers/scripts/with-local-env.sh wp rewrite flush --hard

# Then hit the page in a browser, or:
curl -ks https://culvers.local/ -o /tmp/home.html
grep -c data-component-root /tmp/home.html        # 1+ per page section
grep -oE 'class="[^"]*<bem-root>[^"]*"' /tmp/home.html | head
```

The number of `data-component-root` markers MUST equal the number of
flexible rows on the page. If a component is missing from the markup,
check the editor placeholder: that fallback should appear when
logged-in.

---

## 13. Anti-patterns (do not ship these)

| Anti-pattern | What to do instead |
| ------------ | ------------------ |
| Re-defining `component_width`, `background_*`, `body_text_tone`, `visibility_mobile`, `top_padding`, `bottom_padding` in your component file. | They're added by `ComponentRegistry` to every layout. Just `tab_general` → fields → `tab_padding`. |
| Hand-writing `<img>` tags. | `Image::render($acfImage, [...])`. |
| `<h2>` hard-coded in markup. | `Component::headingTag($c['<prefix>_heading_level'] ?? null)`. |
| Skipping the empty-state branch. | Wrap with `$hasContent` and emit the editor placeholder. |
| Inline arbitrary `text-[42px]` when `text-3xl` is within 2 px. | Snap to the type ladder. See `docs/TYPOGRAPHY-SCALE.md`. |
| Inventing a CSS class (`.my-component-button`) when Tailwind already covers it (`.btn-primary`). | Use the Tailwind utility / existing button class. |
| Reading `get_field(...)` from inside an Alpine module. | Render values into `data-*` attributes from Blade and read those. |
| Putting an external API call in a component Blade. | Wrap in a `final` class under `app/<Subject>/` + WP REST endpoint with nonce + rate-limit. See §8. |
| New documentation file outside `docs/`. | All theme docs live in `docs/`. |

---

## See also

- [`docs/README.md`](README.md) — theme overview and toolchain.
- [`docs/DIRECTORY-POST-TYPES.md`](DIRECTORY-POST-TYPES.md) — recipe for
  adding a new directory CPT (post type + taxonomies + archive +
  single) that hosts these components.
- [`docs/TYPOGRAPHY-SCALE.md`](TYPOGRAPHY-SCALE.md) — Figma → Tailwind
  type ramp. Required reading before touching font sizes.
- [`docs/components/`](components/) — one document per layout in the
  theme.
