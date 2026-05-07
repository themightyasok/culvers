# Event meta (`event_meta`)

Compact "When / Where / Tickets" panel for single event pages: date,
time, location rows separated by hairline rules, plus an optional
accessibility note and a single primary CTA. Sits below the event hero
so visitors can scan the practical details before the long-form
description.

| | |
| --- | --- |
| Layout key | `event_meta` |
| ACF schema | [`app/Components/event_meta.php`](../../app/Components/event_meta.php) |
| Blade view | [`resources/views/components/event-meta.blade.php`](../../resources/views/components/event-meta.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | _(none)_ |
| BEM root | `.event-meta` |
| Used on | Single `culvers_event` posts |

## When to use

On every single event detail page, immediately after the hero. The
date / time / location triplet is intentionally plain text so editors
can write "Thu 12 – Sun 15 June 2026" or "Drop-in all day" without
fighting a date picker.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `event_meta_date_label` | text | `Date` | Column label on the left. |
| `event_meta_date_value` | text | — | e.g. "Thursday 12 June 2026". |
| `event_meta_time_label` | text | `Time` | |
| `event_meta_time_value` | text | — | e.g. "10:00–16:00". |
| `event_meta_location_label` | text | `Location` | |
| `event_meta_location_value` | text | — | e.g. "Centre square". |
| `event_meta_accessibility_note` | textarea (`new_lines: br`) | — | Sensory-friendly hour, BSL availability, etc. |
| `event_meta_cta_label` | text | — | e.g. "Book tickets". |
| `event_meta_cta_url` | url | — | External booking link or internal page. |

## Behaviour notes

- A row is omitted entirely if its `_value` is blank — the label alone
  doesn't render.
- The CTA button uses the canonical `.btn .btn-primary` classes (same
  as every other CTA in the theme).
- External CTAs (URLs starting `http`) open in a new tab with
  `rel="noopener noreferrer"` and a visually-hidden "(opens in new
  tab)" label for screen readers.
- Definition list semantics (`<dl>` / `<dt>` / `<dd>`) so assistive
  tech reads the rows correctly.

## Related components

- [`section_header`](SECTION-HEADER.md) — sits *above* event_meta as
  the page intro on the single event page.
- [`content_section`](CONTENT-SECTION.md) — long-form description
  below event_meta.
- [`career_detail`](CAREER-DETAIL.md) — sibling layout for single
  career pages with a similar split panel.
