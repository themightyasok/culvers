# Component catalogue

Every flexible-content layout in the theme. Each row links to a
component-specific doc with the full ACF schema, behaviour notes, and
related-component guidance.

For the rules every component file follows, read
[`../COMPONENT-AUTHORING.md`](../COMPONENT-AUTHORING.md) first.

## At a glance

| Category | Layout key | Doc | One-liner |
| --- | --- | --- | --- |
| **Section openers** | `section_header` | [SECTION-HEADER.md](SECTION-HEADER.md) | Eyebrow + heading + short body — the small intro band. |
| **Section openers** | `content_section` | [CONTENT-SECTION.md](CONTENT-SECTION.md) | Long-form heading + WYSIWYG body for policy / about-style copy. |
| **Section openers** | `info_block` | [INFO-BLOCK.md](INFO-BLOCK.md) | Heading + subheading + body + CTA + 4-up icon cells. |
| **Heroes** | `hero_slider` | [HERO-SLIDER.md](HERO-SLIDER.md) | Full-bleed Splide carousel — homepage / category landing heroes. |
| **Heroes** | `image_hero` | [IMAGE-HERO.md](IMAGE-HERO.md) | Static full-bleed page header (Contact, About, brand pages). |
| **Cards & rows** | `three_card_block` | [THREE-CARD-BLOCK.md](THREE-CARD-BLOCK.md) | 3-up cards, manual or driven by blog category tabs. |
| **Cards & rows** | `horizontal_scroller` | [HORIZONTAL-SCROLLER.md](HORIZONTAL-SCROLLER.md) | GSAP infinite-scroll strip — logos, gallery, mixed media. |
| **Split layouts** | `shop_split_highlight` | [SHOP-SPLIT-HIGHLIGHT.md](SHOP-SPLIT-HIGHLIGHT.md) | 60/40 or 50/50 olive copy + image, with optional cross-fade tabs. |
| **Split layouts** | `text_image_slider` | [TEXT-IMAGE-SLIDER.md](TEXT-IMAGE-SLIDER.md) | Vertical headline stack that pops in polaroid images on open. |
| **Media** | `video_block` | [VIDEO-BLOCK.md](VIDEO-BLOCK.md) | Self-hosted MP4/WebM with branded play CTA + frame poster. |
| **Lists** | `opening_hours` | [OPENING-HOURS.md](OPENING-HOURS.md) | Day-of-week list with "today" highlight + side illustrations. |
| **Lists** | `faq` | [FAQ.md](FAQ.md) | Centred Canela heading + accordion of question / answer rows. |
| **Maps & wayfinding** | `centre_map` | [CENTRE-MAP.md](CENTRE-MAP.md) | Floor plan + category sidebar; pins highlight on hover. |
| **Maps & wayfinding** | `travel_calculator` | [TRAVEL-CALCULATOR.md](TRAVEL-CALCULATOR.md) | Distance / duration lookup with route-preview map. |
| **Forms** | `contact` | [CONTACT.md](CONTACT.md) | "Getting here" panel + contact form posting to `/wp-json/culvers/v1/contact-form`. |
| **Single-event panel** | `event_meta` | [EVENT-META.md](EVENT-META.md) | Date / time / location / CTA panel for single events. |
| **Single-career panel** | `career_detail` | [CAREER-DETAIL.md](CAREER-DETAIL.md) | Job title + meta + role sections band for single careers. |
| **Shop singles** | `shop_intro_block` | [SHOP-INTRO-BLOCK.md](SHOP-INTRO-BLOCK.md) | Centred intro copy + optional CTA on a cream band. |
| **Shop singles** | `shop_store_details` | [SHOP-STORE-DETAILS.md](SHOP-STORE-DETAILS.md) | Contact / address / social columns. |
| **Shop singles** | `shop_related_shops` | [SHOP-RELATED-SHOPS.md](SHOP-RELATED-SHOPS.md) | "More shops you might enjoy" row using directory cards. |

## How to add a new component

1. Read [`../COMPONENT-AUTHORING.md`](../COMPONENT-AUTHORING.md) — the
   build contract.
2. Drop your `app/Components/<layout>.php` and
   `resources/views/components/<layout-kebab>.blade.php` files.
3. Write the matching `docs/components/<LAYOUT-KEBAB-UPPER>.md` (use
   any existing doc as the template).
4. Add a row to the table above.
5. Run `npm run verify` and smoke-test.

## How to retire a component

1. Search the theme for the layout key
   (`rg "'<layout_key>'" app resources docs`).
2. Migrate any saved data with a `scripts/migrations/<date>-…php`
   one-shot.
3. Delete the four files (PHP, Blade, optional CSS, optional JS) +
   the component doc.
4. Remove the row from the table above.
5. Document the removal at the top of the affected `docs/` page if
   anything cross-references it.

## Cross-cutting documentation

- [`../COMPONENT-AUTHORING.md`](../COMPONENT-AUTHORING.md) — the build
  contract every component follows.
- [`../DIRECTORY-POST-TYPES.md`](../DIRECTORY-POST-TYPES.md) — recipe
  for registering a custom post type that hosts these components.
- [`../TYPOGRAPHY-SCALE.md`](../TYPOGRAPHY-SCALE.md) — the type ramp
  components must snap onto.
- [`../GSAP.md`](../GSAP.md) — animation library + licensing.
- [`../README.md`](../README.md) — theme overview, build, Local.
