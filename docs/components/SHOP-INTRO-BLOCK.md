# Shop — intro block (`shop_intro_block`)

Centred intro copy with optional CTA, set on a cream band with a
geometric Halyard-style background texture. Used on shop singles.

| | |
| --- | --- |
| Layout key | `shop_intro_block` |
| ACF schema | [`app/Components/shop_intro_block.php`](../../app/Components/shop_intro_block.php) |
| Blade view | [`resources/views/components/shop-intro-block.blade.php`](../../resources/views/components/shop-intro-block.blade.php) |
| CSS partial | _(none — tone forced via `Component::bodyTextTone($c, 'light-band')`)_ |
| Alpine module | _(none)_ |
| BEM root | `.shop-intro-block` |

## When to use

Top of a shop single, immediately under the hero. For non-shop pages
needing a similar pattern use [`section_header`](SECTION-HEADER.md)
(small intro band) or [`info_block`](INFO-BLOCK.md) (intro + 4-up
icon cells).

## Editor fields

| Field | Type | Notes |
| --- | --- | --- |
| `intro_body` | wysiwyg (`toolbar: basic`, `media_upload: 0`) | Single centred column. |
| `intro_cta_label` | text | Leave blank to hide. |
| `intro_cta_url` | url | Required when a label is set. |

## Behaviour notes

- Body tone defaults to the light-band override (defends against
  unreadable text on the cream background).

## Related components

- [`section_header`](SECTION-HEADER.md) — small intro band.
- [`shop_split_highlight`](SHOP-SPLIT-HIGHLIGHT.md) — split copy +
  image band (sits below this on shop singles).
