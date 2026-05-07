# Career detail (`career_detail`)

Split job-header band: left sidebar with the job title, meta rows
(Contract Type / Location / Pay) separated by hairline rules, and an
Apply CTA; right column with stacked role sections (About the role,
Work Schedule, Key Responsibilities, Qualifications) — each section is
a heading + WYSIWYG body. Designed to sit between an `image_hero`
above and perks / apply-CTA bands below.

| | |
| --- | --- |
| Layout key | `career_detail` |
| ACF schema | [`app/Components/career_detail.php`](../../app/Components/career_detail.php) |
| Blade view | [`resources/views/components/career-detail.blade.php`](../../resources/views/components/career-detail.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | _(none)_ |
| BEM root | `.career-detail` |
| Figma reference | `51:6450` |
| Used on | Single `culvers_career` posts |

## When to use

On every single career detail page. Pair with `image_hero` above for
the photographic header. The component is intentionally focussed on
the **header band only** — perks, application form, "more roles"
suggestions, etc. are separate components stacked on the page.

## Editor fields

### Job header tab

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `career_job_title` | text | — | Display serif headline shown top-left. |
| `career_job_title_level` | select | `h1` | H1 allowed (and the default — the role title is the page H1). |
| `career_meta` | repeater (0–6, `table` layout) | — | Stacked label/value rows separated by hairline rules. |
| `career_apply_label` | text | `Apply Now` | |
| `career_apply_url` | url | — | Where the Apply button takes the candidate (employer ATS, mailto, internal page). |

#### Meta sub-fields

| Sub-field | Type | Notes |
| --- | --- | --- |
| `item_label` | text | e.g. "Contract Type". |
| `item_value` | text | e.g. "Full-Time". |

### Role sections tab

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `career_section_heading_level` | select | `h2` | Heading level for each role section. |
| `career_sections` | repeater (0–12, `block` layout) | — | One row per section (About / Schedule / Responsibilities / Qualifications). |

#### Section sub-fields

| Sub-field | Type | Notes |
| --- | --- | --- |
| `item_heading` | text | |
| `item_body` | wysiwyg (`toolbar: basic`, `media_upload: 0`) | Use bullet lists for responsibilities / qualifications. |

## Behaviour notes

- Job-title heading-level select defaults to H1 (the role title is
  typically the page's primary heading); the image hero above
  intentionally goes title-less in that arrangement.
- Apply CTA opens in a new tab when the URL starts with `http`.

## Related components

- [`event_meta`](EVENT-META.md) — sibling layout for single events
  with the same split-panel pattern.
- [`shop_store_details`](SHOP-STORE-DETAILS.md) — sibling layout for
  shop singles.
- [`section_header`](SECTION-HEADER.md) — small intro band you can
  stack above this on the page.
