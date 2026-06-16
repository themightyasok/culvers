# Directory custom post types — recipe

Culvers has **six** directory CPTs that follow the same shape:

| Post type | Slug | Archive | Notes |
| --- | --- | --- | --- |
| Shops | `culvers_shop` | `/shops/` | Taxonomies: shop category, retailer type |
| Eat & Drink | `culvers_eat_drink` | `/eat-drink/` | Taxonomies: eat-drink category, type |
| Latest Events | `culvers_event` | `/latest-events/` | **`/whats-on/` is a curated page**, not this archive |
| Latest Offers | `culvers_offer` | `/latest-offers/` | Newest-first sort |
| Latest News | `culvers_news` | `/latest-news/` | Newest-first sort |
| Careers | `culvers_career` | careers archive slug in `DirectoryPostTypes.php` | Department taxonomy |

Card rendering delegates to **`partials/directory-card.blade.php`** via **`DirectoryCardSpec`** / **`DirectoryCardSpecFactory`**. Legacy `directory-{cpt}-card.blade.php` shims may still `@include` the canonical partial.

> Source of truth (code):
> [`app/Directory/`](../app/Directory/) — registration, taxonomy seeders, listing/archive ACF.
> [`app/setup.php`](../app/setup.php) — registration hooks.
> [`app/Config/ComponentPostTypes.php`](../app/Config/ComponentPostTypes.php) — which flexible layouts appear in the picker per post type.
> [`app/ComponentRegistry.php`](../app/ComponentRegistry.php) — builds one ACF field group per allowlist via `registerFlexibleContentGroups()`.

---

## 1. Pattern overview

```
app/Directory/
├── DirectoryPostTypes.php         ← register_post_type + register_taxonomy (all six)
├── DirectoryFlexibleDefaults.php  ← editor defaults for new directory singles
├── ShopFields.php / ShopArchiveFields.php
├── EatDrinkFields.php / EatDrinkArchiveFields.php
├── EventFields.php / EventArchiveFields.php
├── OfferFields.php / OfferArchiveFields.php
├── NewsFields.php / NewsArchiveFields.php
├── CareerFields.php / CareerArchiveFields.php
├── ShopTaxonomySeeder.php … CareerTaxonomySeeder.php
├── Cards/DirectoryCardSpec.php    ← card value object
├── Cards/DirectoryCardSpecFactory.php
└── … live-sync helpers (ShopLiveSync, EatDrinkLiveSync, DirectoryLiveRetailerPage, …)

app/Config/
└── ComponentPostTypes.php         ← layout allowlists per post type

resources/views/
├── archive-culvers-shop.blade.php
├── archive-culvers-eat-drink.blade.php
├── archive-culvers-event.blade.php
├── archive-culvers-offer.blade.php
├── archive-culvers-news.blade.php
├── archive-culvers-career.blade.php
├── single-culvers-*.blade.php     ← thin shells → flexible-components
├── partials/directory-card.blade.php
├── partials/directory-archive-intro.blade.php
├── partials/directory-archive-filter-body.blade.php   ← shops, eat-drink, careers
└── partials/directory-archive-chronological-body.blade.php  ← events, offers, news

resources/scripts/alpine/directory-archive.js  ← filter sidebar (shops / eat-drink / careers)
```

| Archive group | Templates | Interaction |
| --- | --- | --- |
| Filter + grid | shop, eat-drink, career | `directory-archive.js`, shared filter partials |
| Chronological grid | event, offer, news | No filter sidebar; shared chronological partial |

All archive body content uses **`LayoutShell::INNER_MAX_GUTTERED`** (same inner shell as flexible components).

---

## 2. Layout allowlists (`ComponentPostTypes`)

Flexible layouts are **not** globally pickable. `ComponentPostTypes::fieldGroupDefinitions()` registers separate **Page Components** field groups:

| Group key | Post types | Example layouts |
| --- | --- | --- |
| `page` | `page` | Marketing stack: hero, section_header, three_card_block, centre_map, … |
| `shop` | `culvers_shop` | `shop_intro_block`, `shop_store_details`, `shop_related_shops`, … |
| `eat_drink` | `culvers_eat_drink` | Same shape as shops + `shop_related_eat_drink` |
| `event` | `culvers_event` | Includes `event_meta` |
| `event_like` | `culvers_offer`, `culvers_news` | Like events minus `event_meta` |
| `career` | `culvers_career` | Includes `career_detail` |

Every layout in `app/Components/*.php` must appear in exactly one allowlist or `ComponentRegistry::assertAllComponentsAssigned()` throws in dev.

---

## 3. Add a seventh directory CPT — step-by-step

Use **`culvers_partner`** as a worked example (services, partners, etc.).

### Step 1 — Register the post type + taxonomies

Add registration to [`app/Directory/DirectoryPostTypes.php`](../app/Directory/DirectoryPostTypes.php). Match labels, `has_archive`, REST, and rewrite slug to existing CPTs. **Bump `REWRITE_VERSION`** when rewrite rules change.

### Step 2 — Adjust archive queries

In `DirectoryPostTypes::adjustArchiveQueries()`, add the new post type to the correct branch:

- **Alphabetical + filters:** shops, eat-drink, careers (and partner if filter-based).
- **Chronological:** events, offers, news.

### Step 3 — Taxonomy seeder + listing fields

Mirror [`EventTaxonomySeeder.php`](../app/Directory/EventTaxonomySeeder.php) and [`EventFields.php`](../app/Directory/EventFields.php). Add archive options fields if the design needs a custom hero (see `*ArchiveFields.php`).

### Step 4 — Wire `setup.php`

Register the seeder on `init` (priority 15) alongside the existing seeders.

### Step 5 — Register listing/archive ACF in `Fields.php`

Add `Directory\PartnerFields::register()` (and archive fields if needed) in `Fields::registerComponentFields()` after the existing directory field groups.

Flexible content groups are registered automatically via:

```php
foreach ($this->componentRegistry->registerFlexibleContentGroups() as $flexibleContent) {
    acf_add_local_field_group($flexibleContent->build());
}
```

Do **not** call legacy `registerFlexibleContent()` or hand-roll a single global `setLocation()` chain.

### Step 6 — Add layout allowlist

In [`app/Config/ComponentPostTypes.php`](../app/Config/ComponentPostTypes.php):

1. Define `PARTNER_LAYOUTS` (or reuse `EVENT_LIKE_LAYOUTS` if identical).
2. Add a `fieldGroupDefinitions()` entry with `post_types` => `['culvers_partner']` and the layout list.

Reload admin — registry fingerprint auto-clears stale component cache on deploy.

### Step 7 — Templates + card spec

```
resources/views/single-culvers-partner.blade.php
resources/views/archive-culvers-partner.blade.php
```

Single template pattern:

```blade
@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php the_post(); @endphp
    @include('partials.flexible-components')
  @endwhile
@endsection
```

Archive: copy the closest existing archive and `@include` either:

- `partials.directory-archive-filter-body` (filter sidebar), or
- `partials.directory-archive-chronological-body` (date-sorted grid).

Extend **`DirectoryCardSpecFactory`** for the new post type so cards render through **`partials/directory-card.blade.php`**. Keep filter hooks identical (`data-directory-card`, `data-category-slugs`, `data-type-slugs`, `data-sort-title`) if using `directory-archive.js`.

### Step 8 — Verify

```bash
cd app/public/wp-content/themes/culvers
npm run verify

# from app/public:
./wp-content/themes/culvers/scripts/with-local-env.sh wp rewrite flush --hard
./wp-content/themes/culvers/scripts/with-local-env.sh wp post-type list --format=csv | grep culvers_partner
curl -ksI https://culvers.local/partners/ | head -1
```

Component cache clears automatically when registry PHP changes; `wp cache flush` is optional.

---

## 4. Why this is centralised

- **`DirectoryPostTypes`** — one place to register CPTs and bump rewrite version.
- **`ComponentPostTypes`** — editors only see layouts valid for that post type.
- **`directory-card.blade.php`** — one card markup path for archives and three-card CPT mode.
- **Shared archive partials** — filter and chronological shells stay in sync.

Adding a seventh directory is mostly: registration + allowlist + card factory + one archive template.

---

## See also

- [`docs/COMPONENT-AUTHORING.md`](COMPONENT-AUTHORING.md) — flexible component contract.
- [`docs/components/`](components/) — component catalogue.
- [`docs/SCRIPTS.md`](SCRIPTS.md) — directory populate / live-sync CLIs.
- [`docs/README.md`](README.md) — theme overview.
