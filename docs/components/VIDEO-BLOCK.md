# Video block (`video_block`)

Self-hosted MP4 / WebM with a branded play CTA, scaled hover frame,
and a poster image (or first decoded frame as a fallback).

| | |
| --- | --- |
| Layout key | `video_block` |
| ACF schema | [`app/Components/video_block.php`](../../app/Components/video_block.php) |
| Blade view | [`resources/views/components/video-block.blade.php`](../../resources/views/components/video-block.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | [`resources/scripts/alpine/video-block.js`](../../resources/scripts/alpine/video-block.js) |
| BEM root | `.video-block` |

## When to use

A single contained video. For a row of mixed media use
[`horizontal_scroller`](HORIZONTAL-SCROLLER.md) (which supports video
items). For a backdrop video behind a hero, use the General-tab
`background_type=video` field on any component (see
[`COMPONENT-AUTHORING.md`](../COMPONENT-AUTHORING.md) §2 "What the
registry adds for you").

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `video_instructions` | message | — | Editor-only note. |
| `video_file` | file (mp4 / webm, required) | — | |
| `video_poster` | image | — | Shown until play. Without one the theme primes the first decoded frame when supported. |
| `video_play_label` | text | `Play video` | Accessible name for the play CTA. |

## Behaviour notes

- Hover scales the frame slightly; gated by
  `prefers-reduced-motion: reduce`.
- Click on the play CTA enters native fullscreen / inline playback.

## Related components

- [`horizontal_scroller`](HORIZONTAL-SCROLLER.md) — for a row of
  video / image / text items.
- [`hero_slider`](HERO-SLIDER.md) — heroes don't use this block;
  hero imagery sits inside the slider.
