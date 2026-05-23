# Social share (`social_share`)

Centred Canela heading + Instagram / Facebook / WhatsApp icon row.
Profile URLs come from the Customizer footer settings; WhatsApp opens
a share intent for the current singular URL.

| | |
| --- | --- |
| Layout key | `social_share` |
| ACF schema | [`app/Components/social_share.php`](../../app/Components/social_share.php) |
| Blade view | [`resources/views/components/social-share.blade.php`](../../resources/views/components/social-share.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | _(none)_ |
| BEM root | `.social-share` |
| Figma reference | `51:6411` |

## When to use

Bottom-of-single share band on offers, events, and similar singles where
the design calls for a lightweight “share with a friend” row.

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `share_heading` | text | `Share with a friend` | |
| `share_heading_level` | select | `h2` | |

## Behaviour notes

- Instagram and Facebook links render only when the Customizer URLs are
  non-empty and pass `SocialShare::isRenderableUrl()`.
- WhatsApp uses the current singular permalink + title when available.
- Icons are decorative links with visible text labels for accessibility.

## Related components

- [`section_header`](SECTION-HEADER.md) — lighter intro band without share actions.
