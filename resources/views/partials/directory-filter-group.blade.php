@php
  /**
   * Directory filter group — one collapsible filter section (toggle button +
   * radio list with an "All" sentinel followed by N option rows). Used twice
   * per filtered archive (Shop / Eat & Drink / Careers): once for the
   * category-axis filter, once for the type-axis filter.
   *
   * The partial is a controlled child of the surrounding `directoryArchive`
   * Alpine module — it reads/writes whichever state vars the caller wires in
   * via {@see $state_var} / {@see $toggle_var} / {@see $setter}, matching
   * the contract in {@see resources/scripts/alpine/directory-archive.js}.
   *
   * Inputs:
   *   • `$label`            — heading on the toggle button ("Category",
   *                            "Cuisine", "Department", "Retailer type",
   *                            "Venue type", "Contract type", …).
   *   • `$aria_label`       — short sr-only label for the radiogroup.
   *                            Defaults to `$label`.
   *   • `$panel_id`         — DOM id for the `<ul>` panel; also the button's
   *                            `aria-controls` target. Per-archive
   *                            (`directory-category-panel-eat-drink`, etc.).
   *   • `$state_var`        — Alpine state name holding the selected slug
   *                            (`'categorySlug'` | `'typeSlug'`).
   *   • `$toggle_var`       — Alpine state name holding the open/closed flag
   *                            (`'categoryOpen'` | `'retailerOpen'`).
   *   • `$setter`           — Alpine setter method called when an option is
   *                            clicked (`'setCategory'` | `'setType'`). The
   *                            partial inlines `setCategory(slug)` because
   *                            the module exposes both setters as methods.
   *   • `$options`          — `list<array{slug: string, name: string}>` of
   *                            term/option pairs to render. Caller is
   *                            responsible for ordering.
   *   • `$extra_section_classes` — extra classes on the outer
   *                            `<div class="directory-archive__filter-section">`.
   *                            Pass `'pt-2'` for the second group on a page
   *                            so it sits flush with the first.
   *   • `$all_label`        — copy for the "All" sentinel option (default
   *                            `'All'`).
   *
   * Normalisations vs the legacy hand-rolled markup (intentional, both
   * imperceptible at any realistic sidebar width):
   *   • Toggle button uses `gap-3` everywhere. The legacy second group used
   *     `gap-2` — but `justify-between` makes the gap a min-spacing only
   *     and the button is always wider than `label + gap + +/- icon`, so
   *     the difference never materialises on screen.
   *   • Toggle button always carries `focus-visible:rounded-md`. The legacy
   *     first group omitted it; the legacy second group had it. Rounded
   *     focus rings on these section headers are a small a11y improvement
   *     that matches the radio-option buttons below.
   */
  $group_label = (string) ($label ?? '');
  $group_aria_label = (string) ($aria_label ?? $group_label);
  $group_panel_id = (string) ($panel_id ?? '');
  $group_state_var = (string) ($state_var ?? 'categorySlug');
  $group_toggle_var = (string) ($toggle_var ?? 'categoryOpen');
  $group_setter = (string) ($setter ?? 'setCategory');
  $group_options = is_array($options ?? null) ? $options : [];
  $group_extra_section_classes = (string) ($extra_section_classes ?? '');
  $group_all_label = (string) ($all_label ?? __('All', 'culvers'));

  /* JSON_HEX_* keeps the slug safe inside Alpine's `:class="…"` and
     `@click="…"` evaluators no matter what punctuation it contains. */
  $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES;
@endphp
<div class="directory-archive__filter-section{{ $group_extra_section_classes !== '' ? ' ' . $group_extra_section_classes : '' }}">
  <button
    type="button"
    class="flex w-full items-center justify-between gap-3 py-4 text-left font-sans text-xs font-semibold uppercase tracking-widest text-faded-olive transition hover:text-deep-moss focus-visible:rounded-md culvers-focus-ring-compact"
    @click="{{ $group_toggle_var }} = ! {{ $group_toggle_var }}"
    :aria-expanded="{{ $group_toggle_var }}.toString()"
    aria-controls="{{ esc_attr($group_panel_id) }}">
    <span>{{ esc_html($group_label) }}</span>
    <span class="text-lg leading-none text-deep-moss tabular-nums" aria-hidden="true" x-text="{{ $group_toggle_var }} ? '−' : '+'"></span>
  </button>

  <ul
    id="{{ esc_attr($group_panel_id) }}"
    class="directory-archive__filter-list flex flex-col gap-3 pb-5 pt-1"
    role="radiogroup"
    aria-label="{{ esc_attr($group_aria_label) }}"
    x-show="{{ $group_toggle_var }}"
    x-transition.opacity.duration.150ms>
    <li>
      <button
        type="button"
        role="radio"
        class="directory-archive__filter-option flex w-full items-center gap-[14px] py-0.5 text-left focus-visible:rounded-md culvers-focus-ring-compact"
        :class="{{ $group_state_var }} === '' ? 'directory-archive__filter-option--on' : 'directory-archive__filter-option--off'"
        :aria-checked="{{ $group_state_var }} === ''"
        @click="{{ $group_setter }}('')">
        <span class="directory-archive__radio" :class="{{ $group_state_var }} === '' ? 'directory-archive__radio--checked' : ''" aria-hidden="true"></span>
        <span class="font-sans text-xs font-semibold uppercase tracking-widest">{{ esc_html($group_all_label) }}</span>
      </button>
    </li>
    @foreach ($group_options as $opt)
      @php
        $opt_slug = (string) ($opt['slug'] ?? '');
        $opt_name = (string) ($opt['name'] ?? '');
        if ($opt_slug === '' || $opt_name === '') {
            continue;
        }
        $slug_json = json_encode($opt_slug, $json_flags);
      @endphp
      <li>
        <button
          type="button"
          role="radio"
          class="directory-archive__filter-option flex w-full items-center gap-[14px] py-0.5 text-left focus-visible:rounded-md culvers-focus-ring-compact"
          :class="{{ $group_state_var }} === {!! $slug_json !!} ? 'directory-archive__filter-option--on' : 'directory-archive__filter-option--off'"
          :aria-checked="{{ $group_state_var }} === {!! $slug_json !!}"
          @click="{{ $group_setter }}({!! $slug_json !!})">
          <span class="directory-archive__radio" :class="{{ $group_state_var }} === {!! $slug_json !!} ? 'directory-archive__radio--checked' : ''" aria-hidden="true"></span>
          <span class="leading-snug">{{ esc_html($opt_name) }}</span>
        </button>
      </li>
    @endforeach
  </ul>
</div>
