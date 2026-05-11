# ACF flexible components — editor UX

> **Status:** shipped (2026-05-10).
> Treat this file as the durable spec for the ACF page-builder UX. Update when scope changes.

## Tab shape — every layout (max 4 tabs)

Every component registered through `App\ComponentRegistry` renders inside the page-builder with the same four-tab strip:

| Tab | Always present? | Contents |
| --- | --- | --- |
| **Main** | yes | Sectioned in this fixed order — **Layout** (`component_width`) → **Background** (`background_type` + conditional fields, flat, no accordions) → **Content** (component's own non-typography fields) → **Visibility** (`visibility_hide_phone`, `visibility_hide_desktop`). |
| **Typography** | yes | `body_text_tone` (block default) plus any per-element styling the component opts into (colour / size / weight / intra-element padding). |
| **Items** | only when the component declares a top-level repeater | The repeater itself, no extra chrome. |
| **Mobile** | yes | Either real `_*_mobile` override fields, or a clear explanatory message routing the editor to per-row overrides on the **Items** tab when the component has no block-level mobile fields. Never blank. |

The shared section dividers inside **Main** and the per-element dividers components emit inside **Typography** are styled by [`resources/styles/acf-flexible-admin.css`](../resources/styles/acf-flexible-admin.css) (`.culvers-acf-section-head`). Help / explanatory rows use `.culvers-acf-help`.

## Component file shape

A component config under [`app/Components/*.php`](../app/Components) returns this array:

```php
return [
    'label'   => 'Hero — image',
    'display' => 'block',
    'main'        => [/* flat field map */],
    'typography'  => [/* optional flat field map */],
    'items'       => [/* optional flat field map (a repeater goes here) */],
    'mobile'      => [/* optional flat field map of `_*_mobile` overrides */],

    // Optional: override the empty-Mobile-tab message for this component.
    'mobile_empty_message' => __('Per-slide mobile imagery is set inside each row…', 'culvers'),
];
```

`'fields'`, `tab_general`, `tab_structure`, `tab_items`, `tab_media`, `tab_breakpoints`, `tab_padding`, `ResponsiveFields::breakpointTabFields(...)` and the legacy `addLayoutAndBackgroundTab` / `addVisibilityTab` chrome are all gone — components no longer declare ACF tabs themselves.

In-tab sub-section dividers (e.g. inside **Typography** to separate Header / Subheading / Body controls) are emitted via [`Component::sectionDivider()`](../app/Helpers/Component.php). Each entry needs a unique array key (`msg_typo_header`, `msg_typo_body`, …) — AcfBuilder rejects duplicate field names within the same layout.

## Background fields — flat, conditional, never accordions

`background_type` (None / Color / Gradient / Image / Centered Image Card / Video) is shown at the top of the **Background** section. Every related field (colour pickers, gradient angle, image, parallax, video file, YouTube URL, overlay) appears inline with `conditional_logic` keyed off `background_type` so the editor sees only the fields that apply to the chosen type. There are no accordion wrappers — the previous "Solid Colour & Gradient" / "Image & Centred Card" / "Video" / "Overlay" accordions that wouldn't open are removed.

## Visibility model

`visibility_hide_phone` and `visibility_hide_desktop` live in **Main → Visibility**. They map to Tailwind utility bundles via `App\Helpers\ComponentVisibility::gridUtilityClasses()`, which the renderer emits onto the wrapping grid item. Phones = below the `md` breakpoint (768px). Tablet + desktop share one band from `md` upward — there is intentionally no separate tablet-only hide.

## Cascade for mobile content

Per-component `_*_mobile` fields render on the **Mobile** tab. The Blade view uses the explicit pair (e.g. `hero_image` + `hero_image_mobile`) and falls back to the desktop value when the mobile slot is blank. Per-row mobile assets (hero slider per-slide imagery) live on the row sub-fields, not on a layout-level Mobile tab — the **Mobile** tab on those layouts shows a one-line note routing the editor to the **Items** tab.

Mobile fields are only added to a component when the renderer actually consumes them. Adding a UI control that the front end ignores is the exact anti-pattern the editor complained about ("if they cant be opened they should not be there"); do not reintroduce it.

## Verification

- [`php -l`](https://www.php.net/manual/en/features.commandline.options.php) clean across `app/ComponentRegistry.php`, `app/Helpers/Component.php`, and every `app/Components/*.php`.
- `composer lint` (PHPCS) and `composer analyse` (PHPStan level 6) clean.
- `npm run verify` (lint + format-check + Vite build + composer lint + composer analyse) clean.
- Smoke check: `wp eval` builds `ComponentRegistry::registerFlexibleContent()` → 20 layouts, every layout exposes the expected `Main / Typography [/ Items] / Mobile` tab strip.
- Browser smoke check: `culvers.local/wp-admin/post.php?post=54&action=edit` renders the new tabs with section dividers under **Main**, populated **Typography**, repeater on **Items**, and a non-empty **Mobile** tab on every component.
