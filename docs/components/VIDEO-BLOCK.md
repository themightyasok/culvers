# Video block (`video_block`)

Self-hosted MP4 / WebM with a branded play CTA and poster, **or** a
static image in the same branded frame. Editors pick Image or Video
in the CMS (backend toggle — not a front-end control).

| | |
| --- | --- |
| Layout key | `video_block` |
| ACF schema | [`app/Components/video_block.php`](../../app/Components/video_block.php) |
| Blade view | [`resources/views/components/video-block.blade.php`](../../resources/views/components/video-block.blade.php) |
| CSS partial | _(none)_ |
| Alpine module | [`resources/scripts/alpine/video-block.js`](../../resources/scripts/alpine/video-block.js) |
| BEM root | `.video-block` |

## When to use

A single contained media band. Use **Image** when the finished video
is not ready yet. For a row of mixed media use
[`horizontal_scroller`](HORIZONTAL-SCROLLER.md). For a backdrop video
behind a hero, use the General-tab `background_type=video` field on
any component (see [`COMPONENT-AUTHORING.md`](../COMPONENT-AUTHORING.md)).

## Editor fields

| Field | Type | Default | Notes |
| --- | --- | --- | --- |
| `video_instructions` | message | — | Editor-only note. |
| `video_media_type` | button group (`image` / `video`) | `video` | Backend toggle. |
| `video_image` | image (required when Image) | — | Still inside the branded frame; no play UI. |
| `video_file` | file (mp4 / webm, required when Video) | — | |
| `video_poster` | image | — | Video only. Shown until play. |
| `video_play_label` | text | `Play video` | Video only. |

## Behaviour notes

- Image mode: static cover only (no Alpine play control).
- Video mode: click play CTA for inline playback; hover scale gated by
  `prefers-reduced-motion: reduce`.

## Related components

- [`horizontal_scroller`](HORIZONTAL-SCROLLER.md) — for a row of
  video / image / text items.
- [`hero_slider`](HERO-SLIDER.md) — heroes don't use this block;
  hero imagery sits inside the slider.
