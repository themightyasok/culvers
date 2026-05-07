# Content section (`content_section`)

Long-form heading + WYSIWYG body. The simplest "drop in some prose"
block — used for policy pages, About, Leasing pitch text, anywhere
the editor wants free reign with paragraphs / lists / links / images.

| | |
| --- | --- |
| Layout key | `content_section` |
| ACF schema | [`app/Components/content_section.php`](../../app/Components/content_section.php) |
| Blade view | [`resources/views/components/content-section.blade.php`](../../resources/views/components/content-section.blade.php) |
| CSS partial | _(none — uses `.prose` plugin classes)_ |
| Alpine module | _(none)_ |
| BEM root | `.content-section` |

## When to use

For long-form copy — multiple paragraphs, lists, inline links / images,
nested headings. For the small intro band pattern (eyebrow + heading +
1–3 line body) use [`section_header`](SECTION-HEADER.md). For an intro
that ends in a CTA + 4-up icon cells use [`info_block`](INFO-BLOCK.md).

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `content_heading` | text | — | Optional. |
| `content_heading_level` | select | `h2` | H1 allowed (long policy pages may legitimately host the page H1 here). |
| `content_body` | wysiwyg (`toolbar: full`, `media_upload: 1`) | — | Full editor — paragraphs, lists, links, images, blockquotes. |

## Behaviour notes

- Renders the body inside `.prose .prose-deep-moss` (the
  `@tailwindcss/typography` plugin) so spacing, list bullets,
  blockquote rules, etc. all match across pages.
- This is one of the few components that **does not strip horizontal
  inset gutters** — it inherits the main grid gutters as its frame
  (so prose stays in line with adjacent components).

## Related components

- [`section_header`](SECTION-HEADER.md) — small intro band only.
- [`info_block`](INFO-BLOCK.md) — heading + body + CTA + 4-up icon
  cells.
- [`faq`](FAQ.md) — for question / answer prose use the FAQ
  accordion instead of a wall of headings inside `content_section`.
