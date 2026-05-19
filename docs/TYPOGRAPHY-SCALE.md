# Culver Square type ramp

**Families (Society Brand Guidelines, March 26):** **Canela** — display titles only, **Light (300)** and **Regular (400)** weights, self-hosted from `resources/fonts/canela` via `resources/styles/fonts-canela.css` (bundled by Vite). **Halyard Display** — body/UI sans, loaded from Adobe Fonts (Typekit kit enqueued in `app/Assets/FrontendAssets.php`). **Commuter Sans** — uppercase labels / small-caps lines via `font-label`. Tailwind maps: `font-heading` → Canela, `font-sans` → Halyard stack, `font-label` → Commuter. Editor choices for components go through `App\Helpers\Typography`.

Font **sizes** live in `resources/styles/theme.tokens.css` (`@theme`). The theme uses **only stock Tailwind utility names** (`text-xs`..`text-9xl`). Default Tailwind values for the body tier (`text-xs`..`text-2xl`) are kept so external Tailwind examples behave as expected; only the display tier (`text-3xl`..`text-9xl`) is retuned to the Culver Square display ladder (Canela roles on the front end). Each `--text-*` carries its own `--text-*--line-height`, so the type token already delivers the design line-height — only override `leading-*` when intentionally diverging.

**Rules:**

- Pick **one** stock `text-*` utility per element. Pair with stock `tracking-*` / `leading-*` utilities, or arbitrary values (`tracking-[0.22em]`, `leading-[26px]`) when Figma sits between defaults.
- Do **not** invent new `text-display-*` / `text-caption` / `text-prose-md` / `text-hero-fluid` semantic tokens. Snap close-fit Figma values onto the ladder; the four documented snap losses below are the only intentional ≤ 2 px deviations.
- Numerical pattern extensions are allowed where Tailwind's own scale stops short: `--container-8xl` (continues `max-w-{xs..7xl}` → `8xl` for the 1440 px shell) and `--z-index-{60,70,80}` (continues `z-{10..50}` → `60/70/80` for the header / drawer / skip-link stack).

## Type ladder

Body tier keeps Tailwind defaults; display tier (`text-3xl`+) is retuned to Figma.

| Tailwind class | Size | Line height | Notes / Figma role |
|----------------|------|-------------|--------------------|
| `text-xs` | **12 px** | 24 px | Action labels, eyebrows, footer legal copy (10 px Figma snaps here +2) |
| `text-sm` | **14 px** | 20 px | Directory card sub-line, captions |
| `text-base` | **16 px** | 24 px | Primary nav, mega-nav branch titles, footer body small (15 px snaps here −1) |
| `text-lg` | **18 px** | 26 px | Footer column body / prose, hero eyebrow (17 px snaps here +1) |
| `text-xl` | **20 px** | 1.3 | Large body, large eyebrow |
| `text-2xl` | **24 px** | 26 px | Mega sublinks, directory retail titles, store details (22 px snaps here +2) |
| `text-3xl` | **32 px** | 1.1 | Footer column titles, store heading, footer newsletter (34 px snaps here −2) |
| `text-4xl` | **40 px** | 1.1 | H3 — section sub-headings, mega panel heading |
| `text-5xl` | **48 px** | 1.1 | Mega tile labels |
| `text-6xl` | **58 px** | 1.15 | Section titles (Opening hours, Related shops) |
| `text-7xl` | **64 px** | 1.2 | H2 — page section titles |
| `text-8xl` | **84 px** | 1 | H1 — primary page heading |
| `text-9xl` | **96 px** | 1 | Hero (Discover…) |

### Snap losses (intentional, ≤ 2 px)

| Figma value | Snapped to | Where | Δ |
|-------------|-----------|-------|---|
| 10 px legal copy | `text-xs` (12 px) | Footer legal row, copyright + credit | +2 px |
| 17 px footer body @ md | `text-lg` (18 px) | Footer newsletter body | +1 px |
| 22 px directory cards / mega sublinks | `text-2xl` (24 px) | Mega `.mega-nav__sublink`, directory cards | +2 px |
| 34 px footer newsletter @ lg | `text-3xl` (32 px) | Footer newsletter heading at lg breakpoint | −2 px |
| 15 px primary nav | `text-base` (16 px) | Mega-nav top-level branch labels | −1 px |
| 13 px newsletter email field | `text-xs` (12 px) | Footer newsletter input | −1 px |
| 14.5 px mobile drawer CTAs | `text-sm` (14 px) + `tracking-widest` | Header mobile bottom actions | −0.5 px size; tracking widened |
| 36 px mobile nav titles | `text-4xl` (40 px) | Header drawer section links | +4 px |
| 42 px info-block tile titles | `text-4xl` (40 px) | Info block grid headings | −2 px |
| 46 px three-card titles @ md | `text-5xl` (48 px) | Three-card block card headings | +2 px |

### Allowed arbitrary display sizes (documented exceptions)

| Arbitrary | Where | Why kept |
|-----------|-------|----------|
| `lg:text-[7.75rem]` (124 px) | `image-hero` title @ lg | Between `text-8xl` (84 px) and full-bleed Figma hero; no stock step |
| `leading-[1.05]` / `leading-none` on hero | `image-hero` | Tight display lockup at lg |

## Pill / label cluster — `.btn` is canonical

All uppercase Commuter pill CTAs share **`.btn`** (+ variant: `btn-primary`, `btn-outline`, `btn-dark`). The only non-stock font size in that cluster is **`text-[13px]`** on `.btn` itself (Figma 12.887 px → 13 px snap). Consumers:

- Directory filter pill (`directory-filter-pill` partial) — `btn btn-primary`
- Three-card filter tabs — `btn btn-outline` + `aria-[selected=true]:*` paint overrides
- Card hover CTAs — `btn btn-outline`
- Form submit rows — `btn` + `btn-form` size modifier

Do **not** add new `text-[11px]` / `text-[12.887px]` / `md:text-[13px]` on pills outside `.btn`.

## Form controls

Text inputs, selects, and textareas in contact / travel-calculator / FAQ answers use **`text-base`** (`16 px`, −1 px vs legacy 15 px Figma body fields) with **`leading-[1.32]`** where the design specified 1.32 line height.

## Tracking — stock Tailwind utilities

| Utility | Value | Where used |
|---------|-------|-------------|
| `tracking-tight` | −0.025em | Display headings (most large Canela / `font-heading` blocks) |
| `tracking-wider` | 0.05em | Pill CTAs (`.btn`, directory filter pill, video outline button) |
| `tracking-widest` | 0.1em | All uppercase action labels, footer column links / legal row, footer newsletter input, mega CTAs (8 % Figma values snap here) |

For Figma values beyond `tracking-widest`, use arbitrary utilities — these are the only non-default tracking values in the theme:

| Arbitrary | Where |
|-----------|-------|
| `tracking-[0.2em]` | Hero slider eyebrow (`text-lg` uppercase), shop image hero subtitle (mobile) |
| `tracking-[0.22em]` | Policy / legal hero subtitle |
| `tracking-[0.28em]` | Shop image hero subtitle from `md` |

## Leading — bundled in type tokens; otherwise stock Tailwind utilities

Most elements just inherit the `--text-*--line-height` carried by their `text-*` token. When a design intentionally overrides:

| Utility | When |
|---------|------|
| `leading-tight` (1.25) / `leading-snug` (1.375) / `leading-normal` (1.5) / `leading-relaxed` (1.625) / `leading-loose` (2) | Standard overrides |
| `leading-[1.05]` / `leading-[1.08]` / `leading-[1.12]` / `leading-[1.15]` | Display headings overriding the type-token default at a specific breakpoint (policy hero, footer newsletter heading) |
| `leading-[26px]` | Opening-hours rows aligned to body rhythm |
| `leading-[30px]` | Pill button label rhythm (`.btn`, directory filter pill) |

## Containers — stock Tailwind names + one extension

| Utility | Value | Where |
|---------|-------|-------|
| `max-w-7xl` | 80 rem (1280 px) | Standard wide content |
| `max-w-8xl` | **90 rem (1440 px)** — extension | Site shell (header, footer columns, content) |

The mega panel uses `max-w-[1348px]` and the page header uses `max-w-[1800px]` — both kept as stock arbitrary values rather than minted as new tokens.

## z-index — stock Tailwind 10..50 + extensions for header stack

| Utility | Value | Where |
|---------|-------|-------|
| `z-10`..`z-50` | Stock | Body content stacking |
| `z-60` | Extension | Mega-nav panel above the bar |
| `z-70` | Extension | Mobile drawer |
| `z-80` | Extension | Skip-link (focus-visible) |

## Colour tokens used by type

| Token | Hex | Typical type pairing |
|-------|-----|----------------------|
| `text-purelinen` | `#ededeb` | Footer “Site by” label (Figma Off White / Purelinen) |
| `text-lighter-cream` | `#fffefa` | Footer legal row, column headings |

Hairline rules in component CSS use `color-mix(in srgb, var(--color-*), …)` — no raw hex in stylesheets.

When Figma updates, change **`theme.tokens.css`** first, then refresh this file.
