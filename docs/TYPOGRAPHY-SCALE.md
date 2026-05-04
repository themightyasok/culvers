# Culver Square type ramp

Tailwind utilities **`text-micro`** plus **`text-xs`** through **`text-9xl`** are the **only** font-size tokens. Values are defined in `resources/styles/theme.tokens.css` (`@theme`). **Every step uses an even pixel size** (after merges).

**Rule:** Pick **exactly one** class per role. Do not duplicate the same Figma role under another utility.

## Merges applied (Figma → single token)

| Merge | Token | Even landing |
|-------|--------|----------------|
| 12px + ~12.887px pills | `text-xs` | **12px** |
| 14px utilities + 15px nav | `text-sm` | **16px** |
| ~22px cards + 24px store values | `text-xl` | **24px** |
| 40px H3 + 42px stats | `text-3xl` | **40px** |
| ~45.714px mega tiles | `text-4xl` | **48px** |
| 58px section titles + 64px H2 | `text-5xl` | **64px** |

**Left unmerged (by design):** **10px** legal (`text-micro`), **18px** footer links (`text-base`), **20px** large body / eyebrow base (`text-lg`), **32px** H4 (`text-2xl`), **84px** H1 (`text-6xl`), **96px** hero (`text-7xl`).

## Mapping (Tailwind → px → Figma source)

| Tailwind class | Size | Line height | Figma / notes |
|----------------|------|--------------|----------------|
| `text-micro` | **10px** | 24px | Footer legal — Commuters uppercase (~10px); kept separate from `text-xs` |
| `text-xs` | **12px** | 24px | Action Label variable + pill CTAs (replaces ~12.887px artwork) |
| `text-sm` | **16px** | 24px | Merged **Centre Map / Getting Here (14)** + **primary nav (15)** |
| `text-base` | **18px** | 26px | Footer column links — kept separate from **20px** body |
| `text-lg` | **20px** | 1.3 | Large body variable; hero eyebrow uses **`text-lg uppercase tracking-kicker`** |
| `text-xl` | **24px** | 30px | Merged retail card titles (**22**) + store detail values (**24**) |
| `text-2xl` | **32px** | 1.1 | **Desktop/Titles/H4 Subtitle** |
| `text-3xl` | **40px** | 1.1 | **Desktop/Titles/H3** + former **42px** stat titles |
| `text-4xl` | **48px** | 1.1 | Former fractional **~45.714px** homepage mega labels |
| `text-5xl` | **64px** | 1.2 | **Desktop/Titles/H2** + former **58px** section titles (“Opening Hours”, etc.) |
| `text-6xl` | **84px** | 1 | **Desktop/Titles/H1 Title** |
| `text-7xl` | **96px** | 1 | Hero headline (“Discover…”) |
| `text-8xl` | **112px** | 1 | Reserve — even step; no matching Figma token yet |
| `text-9xl` | **128px** | 1 | Reserve — even step; no matching Figma token yet |

## Components that tweak `text-xs`

**Pill CTA:** still **`text-xs` (12px)**. Use **`leading-[30px]`** (even) and **`tracking-cta`** via `.btn` — do not use `tracking-label` on pills.

## Tracking utilities

| Token | Value | Use |
|-------|-------|-----|
| `tracking-label` | 1px | Action labels (`text-xs` + label tracking) |
| `tracking-kicker` | 4px | Hero eyebrow (`text-lg uppercase`) |
| `tracking-cta` | ~0.644px | Pill buttons (via `.btn`) |

When Figma updates, change **`theme.tokens.css`** first, then this table.
