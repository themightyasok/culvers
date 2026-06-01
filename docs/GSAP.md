# GSAP stack

## Licensing

This theme bundles **GSAP** from npm. **ScrollSmoother** and **ScrollTrigger** are Club/bonus plugins — ensure your project has the correct [GreenSock licensing](https://gsap.com/licensing/) before production use.

## Architecture

| File | Role |
| --- | --- |
| `resources/scripts/utils/gsap-manager.js` | Bootstrap: registers plugins, creates ScrollSmoother on desktop, exposes `window.gsap`, `window.ScrollTrigger`, `window.smoother`, ticker registration for horizontal scroller |
| `resources/scripts/utils/page-anchor.js` | In-page hash scroll with header offset via ScrollSmoother when available |
| `resources/scripts/utils/background-parallax-manager.js` | ScrollTrigger scrub on footer/newsletter parallax images (desktop ≥1024px, aligned with ScrollSmoother) |
| `resources/scripts/alpine/horizontal-scroller.js` | GSAP Observer + ticker for infinite logo strip |
| `resources/scripts/app.js` | Init order: `Alpine.start()` → `gsapManager.init()` → `initPageHashNavigation()` |

## ScrollSmoother

- **Desktop only:** `(min-width: 1024px)` in `gsap-manager.js`
- DOM: `#smooth-wrapper` > `#smooth-content` in `layouts/app.blade.php`
- Mobile fires `gsap:smoother:ready` with `smoother: null` so listeners can proceed without smooth scroll
- Layout refresh: `app.js` debounces `ScrollTrigger.refresh()` / `smoother.refresh()` — defers during hash navigation (`isHashNavigationActive()`)

## ScrollTrigger

- Global config in `gsap-manager.js` (`autoRefreshEvents` includes `resize`)
- Components must not kill unrelated triggers — `horizontal-scroller.js` removes triggers inside its root on setup (latent footgun if parallax nested inside scroller)

## In-page anchors & programmatic scroll

Mega menu and header utility links use URLs like `/plan-my-visit/#centre-map`. `page-anchor.js`:

1. Intercepts same-page hash clicks
2. Opens accordion rows when target lives inside `text-image-slider`
3. Scrolls via `smoother.scrollTo(target, true, 'top {offset}px')` or native `window.scrollTo`

**Also uses `scrollToElement` / `whenScrollReady`:** directory archive deep-links (`?category=`), travel-calculator map reveal after search.

## Reduced motion

Horizontal scroller falls back to static/disabled-scroll modes when `prefers-reduced-motion: reduce`. Hero slider respects reduced motion separately — see component docs.

## Adding motion to a new component

1. Prefer CSS transitions + Alpine for simple UI
2. Use ScrollTrigger only when scroll-scrubbed motion is required
3. Wait for `gsap:smoother:ready` before measuring scroll positions on desktop
4. Do not call `window.scrollTo` or `scrollIntoView` directly on desktop — use `page-anchor.js` (`scrollToElement`, `whenScrollReady`). Directory archive deep-links and travel-calculator map reveal already follow this path.

## Further reading

- `docs/COMPONENT-AUTHORING.md` §6 (Alpine init order)
- `resources/scripts/utils/gsap-manager.js` (source)
