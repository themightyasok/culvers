# Editing flexible page components (ACF)

Pages (and some CPT singles) are built from **Page Components** — an ACF Flexible Content field named `components`.

## What editors see

Each row is one layout (e.g. Hero slider, Section header, Centre map). The registry adds up to four tabs per layout:

| Tab | Contents |
| --- | --- |
| **Main** | Primary content fields (headings, body, images, CTAs). A theme note explains that grid span and band colours are fixed in code. |
| **Typography** | Optional — only on layouts that expose extra type/colour controls (e.g. hero title tone). |
| **Items** | Optional — repeaters (slides, FAQ rows, cards, map categories, etc.). |
| **Mobile** | Optional — overrides that apply **only below 768px** (`md`). Blank = inherit desktop value. |

There is **no** Layout & background tab, **no** Visibility tab, and **no** per-row width/hide toggles. Those were removed in favour of code-authoritative layout chrome.

## ACF 6.5+ built-in UX

- Rename a layout instance (friendly label only — layout key unchanged)
- **Disable** a row without deleting (data kept; front end skips it)
- Collapse / expand all rows
- Drag to reorder

## Visibility

Row visibility is **ACF disable only** (`acf_fc_layout_disabled`). There are no “hide on mobile/tablet” fields.

## After a theme deploy

If new layouts or fields are missing in the editor:

1. Hard refresh the admin page (component registry cache auto-clears when theme PHP changes — see `ComponentCache::invalidateIfRegistrySourcesChanged()`).
2. Check **Settings →** admin notices for **Culvers component registry — load errors** or **Culvers ACF bootstrap failed**.
3. If fields still look stale on a host with persistent object cache, ask dev to run `wp cache flush` once.

## Developers

Authoring contract: `docs/COMPONENT-AUTHORING.md`
