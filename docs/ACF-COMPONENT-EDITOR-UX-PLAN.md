# ACF flexible components — editor UX

> **Status:** shipped (2026-05-10).
> Treat this file as the durable spec for the ACF page-builder UX. Update when scope changes.

## Tab shape — every layout (max 4 tabs)

Every component registered through `App\ComponentRegistry` renders inside the page-builder with the same four-tab strip:

| Tab | Always present? | Contents |
| --- | --- | --- |
| **Main** | yes | Theme-controlled grid/surface note, then **Content** (component's own fields from `main`). Row width and background are fixed in PHP — not editor-driven. |
| **Typography** | only when the component declares fields in `typography` | Per-element styling the component opts into (colour / size / weight / intra-element padding). Block body tone is fixed in code. |
| **Items** | only when the component declares a top-level repeater or post picker in `items` | The repeater / picker itself, no extra chrome. |
| **Mobile** | yes | Either real `_*_mobile` override fields from `mobile`, or a clear explanatory message routing the editor to per-row overrides on the **Items** tab when the component has no block-level mobile fields. Never blank. |

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

## Layout chrome — code-authoritative

Row width and background are merged after sanitization via [`ComponentLayoutChrome`](../app/Helpers/ComponentLayoutChrome.php). The ACF Background UI was removed — surfaces are fixed in code. Adjust per-layout defaults with the `culvers_component_layout_chrome` filter.

## Cascade for mobile content

Per-component `_*_mobile` fields render on the **Mobile** tab. The Blade view uses the explicit pair (e.g. `hero_image` + `hero_image_mobile`) and falls back to the desktop value when the mobile slot is blank. Per-row mobile assets (hero slider per-slide imagery) live on the row sub-fields, not on a layout-level Mobile tab — the **Mobile** tab on those layouts shows a one-line note routing the editor to the **Items** tab.

Mobile fields are only added to a component when the renderer actually consumes them. Adding a UI control that the front end ignores is the exact anti-pattern the editor complained about ("if they cant be opened they should not be there"); do not reintroduce it.

## Verification

- [`php -l`](https://www.php.net/manual/en/features.commandline.options.php) clean across `app/ComponentRegistry.php`, `app/Helpers/Component.php`, and every `app/Components/*.php`.
- `composer lint` (PHPCS) and `composer analyse` (PHPStan level 6) clean.
- `npm run verify` (lint + format-check + Vite build + composer lint + composer analyse) clean.
- Smoke check: `wp eval` builds `ComponentRegistry::registerFlexibleContent()` → all layouts expose the expected `Main [/ Typography] [/ Items] / Mobile` tab strip.
- Browser smoke check: `culvers.local/wp-admin/post.php?post=54&action=edit` renders the new tabs with section dividers under **Main**, populated **Typography** when declared, repeater on **Items**, and a non-empty **Mobile** tab on every component.
