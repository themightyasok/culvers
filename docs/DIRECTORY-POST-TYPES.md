# Directory custom post types — recipe

Culvers has four directory CPTs that all follow the same shape so editors
get one mental model:

| Post type           | Slug          | Archive       | Taxonomies                                                       | Card partial                                              |
| ------------------- | ------------- | ------------- | ---------------------------------------------------------------- | --------------------------------------------------------- |
| Shops               | `culvers_shop`       | `/shops/`      | `culvers_shop_category`, `culvers_shop_type`              | `partials/directory-shop-card.blade.php`                  |
| Eat & Drink         | `culvers_eat_drink`  | `/eat-drink/`  | `culvers_eat_drink_category`, `culvers_eat_drink_type`    | `partials/directory-eat-drink-card.blade.php`             |
| What's On (events)  | `culvers_event`      | `/whats-on/`   | `culvers_event_category`                                  | `partials/directory-event-card.blade.php`                 |
| Careers             | `culvers_career`     | `/careers/`    | `culvers_career_department`                               | `partials/directory-career-card.blade.php`                |

All four use the **same single template** (a thin `flexible-components`
shell) so the page body is built from the standard library of components
documented in [`components/`](components/).

> Source of truth (code):
> [`app/Directory/`](../app/Directory/) — registration, taxonomy
> seeders, ACF listing fields.
> [`app/setup.php`](../app/setup.php) — registration hooks.
> [`app/ComponentRegistry.php`](../app/ComponentRegistry.php) — the
> flexible-content `setLocation()` chain that opts each post type
> into Page Components.

---

## 1. Pattern overview

```
app/Directory/
├── DirectoryPostTypes.php        ← register_post_type + register_taxonomy for all four
├── ShopFields.php                ← ACF "listing fields" (logo, hours line) for shop cards
├── ShopArchiveFields.php         ← ACF options page for the /shops/ archive customisation
├── ShopArchiveThreeCard.php      ← optional "stories" strip below the shops archive
├── DirectoryFlexibleDefaults.php ← editor-only defaults for new directory singles
├── ShopTaxonomySeeder.php        ← seeds default category / type terms
├── EatDrinkFields.php            ← Eat & Drink listing fields
├── EatDrinkTaxonomySeeder.php
├── EventFields.php               ← Event card date / time / location lines
├── EventTaxonomySeeder.php
├── CareerFields.php              ← Career listing fields (employment type, location)
└── CareerTaxonomySeeder.php

resources/views/
├── archive-culvers-shop.blade.php
├── archive-culvers-eat-drink.blade.php
├── archive-culvers-event.blade.php
├── archive-culvers-career.blade.php
├── single-culvers-shop.blade.php
├── single-culvers-eat-drink.blade.php
├── single-culvers-event.blade.php
└── single-culvers-career.blade.php
```

Each archive template uses the existing
`resources/scripts/alpine/directory-archive.js` Alpine module for
filter sidebar interaction (Eat & Drink + Shops). Events and Careers
opt out of the filter sidebar — Events shows a chronological grid,
Careers groups roles by department.

---

## 2. Add a new directory CPT — step-by-step

If you need to add a fifth directory (services, partners, anything
similar), follow this recipe:

### Step 1 — Register the post type + taxonomies

Add the registration to
[`app/Directory/DirectoryPostTypes.php`](../app/Directory/DirectoryPostTypes.php).
Keep the labels translatable, set `has_archive` to a slug, and bump
`REWRITE_VERSION` so the next request flushes rewrite rules.

```php
private static function registerPartnerPostType(): void
{
    register_post_type(
        'culvers_partner',
        [
            'labels' => [
                'name' => __('Partners', 'culvers'),
                'singular_name' => __('Partner', 'culvers'),
                // … rest matches the other directory CPTs.
            ],
            'description' => __('…', 'culvers'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'has_archive' => 'partners',
            'menu_icon' => 'dashicons-groups',
            'menu_position' => 26,
            'supports' => ['title', 'thumbnail', 'editor', 'excerpt', 'revisions'],
            'rewrite' => ['slug' => 'partners', 'with_front' => false],
            'show_in_rest' => true,
            'capability_type' => 'post',
        ]
    );
}
```

Add a `registerPartnerTaxonomies()` companion. Hierarchical, public,
admin-column, REST-enabled — the exact options used by the existing
four CPTs.

Wire both into `register()`:

```php
public static function register(): void
{
    self::registerShopPostType();
    self::registerShopTaxonomies();
    // …existing CPTs…
    self::registerPartnerPostType();
    self::registerPartnerTaxonomies();

    self::maybeFlushRewrites();
    self::adjustArchiveQueries();
}
```

If you change anything that needs new rewrite rules, **bump
`self::REWRITE_VERSION`** by one. The next request runs
`flush_rewrite_rules(false)` automatically.

### Step 2 — Adjust archive queries (optional)

`adjustArchiveQueries()` controls `posts_per_page`, `orderby`, `order`
on the main query for each archive. Add your new post type to the
appropriate branch (alphabetical for shops/eat-drink/careers,
chronological for events).

### Step 3 — Add the taxonomy seeder

Create `app/Directory/PartnerTaxonomySeeder.php` mirroring
[`EventTaxonomySeeder.php`](../app/Directory/EventTaxonomySeeder.php).
It runs once on first init, gated by an option flag so editors can
delete seeded terms without them re-appearing.

```php
final class PartnerTaxonomySeeder
{
    private const OPTION_KEY = 'culvers_partner_terms_seeded';

    public static function maybeSeed(): void
    {
        if ((bool) get_option(self::OPTION_KEY, false)) {
            return;
        }
        foreach (self::categoryNames() as $name) {
            if ($name === '' || term_exists($name, 'culvers_partner_category')) {
                continue;
            }
            wp_insert_term($name, 'culvers_partner_category');
        }
        update_option(self::OPTION_KEY, '1', true);
    }

    /** @return list<string> */
    private static function categoryNames(): array
    {
        return [/* …default term names… */];
    }
}
```

### Step 4 — Add the listing ACF field group

Create `app/Directory/PartnerFields.php` mirroring
[`EventFields.php`](../app/Directory/EventFields.php). These are the
fields shown on the **single** post edit screen that drive the
**archive card** rendering — keep them lean.

### Step 5 — Wire setup.php

Three places in [`app/setup.php`](../app/setup.php):

```php
// 1. The init action that registers post types — automatic via DirectoryPostTypes::register().
// 2. The init action that runs the taxonomy seeder:
add_action('init', static function (): void {
    Directory\ShopTaxonomySeeder::maybeSeed();
    Directory\EatDrinkTaxonomySeeder::maybeSeed();
    Directory\EventTaxonomySeeder::maybeSeed();
    Directory\CareerTaxonomySeeder::maybeSeed();
    Directory\PartnerTaxonomySeeder::maybeSeed();   // ← add yours
}, 15);
```

And register the listing fields in
[`app/Fields.php`](../app/Fields.php):

```php
private function registerComponentFields(): void
{
    Directory\DirectoryFlexibleDefaults::register();
    $flexibleContent = $this->componentRegistry->registerFlexibleContent();
    acf_add_local_field_group($flexibleContent->build());
    Directory\ShopFields::register();
    Directory\ShopArchiveFields::register();
    Directory\EatDrinkFields::register();
    Directory\EventFields::register();
    Directory\CareerFields::register();
    Directory\PartnerFields::register();   // ← add yours
}
```

### Step 6 — Opt the post type into Page Components

Page Components (the flexible content) is registered against `page` and
the directory CPTs in
[`app/ComponentRegistry.php`](../app/ComponentRegistry.php). Extend the
chain:

```php
$components
    ->setLocation('post_type', '==', 'page')
    ->or('post_type', '==', 'culvers_shop')
    ->or('post_type', '==', 'culvers_eat_drink')
    ->or('post_type', '==', 'culvers_event')
    ->or('post_type', '==', 'culvers_offer')
    ->or('post_type', '==', 'culvers_news')
    ->or('post_type', '==', 'culvers_career')
    ->or('post_type', '==', 'culvers_partner');   // ← add yours
```

Without this line the new CPT will not show the **Page Components**
field on its edit screen.

### Step 7 — Templates

```
resources/views/single-culvers-partner.blade.php   ← thin shell
resources/views/archive-culvers-partner.blade.php  ← grid + (optional) filter
resources/views/partials/directory-partner-card.blade.php
```

The single template is always the same:

```blade
@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php the_post(); @endphp
    @include('partials.flexible-components')
  @endwhile
@endsection
```

The archive template can either:

1. **Mirror Shops / Eat & Drink** — filter sidebar +
   `data-directory-card`/`data-category-slugs`/`data-type-slugs`/
   `data-sort-title` on each card, `x-data="directoryArchive"` on the
   grid (existing Alpine module covers it for free).
2. **Mirror What's On** — chronological grid with no sidebar.
3. **Mirror Careers** — group by primary taxonomy term.

Pick the closest existing pattern and copy it; do not invent a fourth
shape until a real design demands one.

The card partial uses the cards' BEM block
(`.directory-shop-card`, `.directory-event-card`, …). Keep the
selector hooks (`data-directory-card`, `data-category-slugs`, etc.)
identical to the Shops card so the existing filter Alpine works
without modification.

### Step 8 — Verify

```bash
cd app/public/wp-content/themes/culvers
npm run verify

# from app/public:
./wp-content/themes/culvers/scripts/with-local-env.sh wp cache flush
./wp-content/themes/culvers/scripts/with-local-env.sh wp rewrite flush --hard
./wp-content/themes/culvers/scripts/with-local-env.sh wp post-type list --format=csv | grep culvers_partner
./wp-content/themes/culvers/scripts/with-local-env.sh wp taxonomy list --format=csv | grep culvers_partner
curl -ksI https://culvers.local/partners/ | head -1   # expect HTTP/2 200
```

---

## 3. Why this is centralised

`DirectoryPostTypes` registers everything in one place so:

- The rewrite-rule flush version is one number to bump for any
  directory schema change.
- Adding a fifth directory is half an hour, not a half day.
- Removing a directory is one PR — drop the registration block, the
  archive template, the card partial, and the relevant `or()` clause
  in `ComponentRegistry`. The CPT goes away cleanly.

---

## See also

- [`docs/COMPONENT-AUTHORING.md`](COMPONENT-AUTHORING.md) — what to
  build *inside* the flexible components on each single post.
- [`docs/components/`](components/) — the component catalogue.
- [`docs/README.md`](README.md) — theme overview.
