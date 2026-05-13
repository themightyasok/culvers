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
   *                            Used only when a page needs legacy spacing tweaks.
   *   • `$all_label`        — copy for the "All" sentinel option (default
   *                            `'All'`).
   *
   * Normalisations vs the legacy hand-rolled markup (intentional, both
   * imperceptible at any realistic sidebar width):
   *   • Section hairlines (`directory-archive.css`) enforce Figma divider
   *     rhythm; callers should avoid `extra_section_classes` spacer hacks.
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

  /* JSON_HEX_* keeps JSON safe; slugs are then HTML-escaped for double-quoted
     attributes so inner `"` from json_encode does not terminate the attribute
     (which broke Alpine for slugs like centre-management). */
  $json_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES;
@endphp
<div class="directory-archive__filter-section{{ $group_extra_section_classes !== '' ? ' ' . $group_extra_section_classes : '' }}">
  {{-- Figma 51:7443 (+ related filter frames): divider under heading row; CATEGORY uses Commuters SemiBold 12 /
       lh 24 / tracking 1; vertical inset ≈ py-5 between twin hairlines. --}}
  <div class="directory-archive__filter-heading">
    <button
      type="button"
      class="flex w-full items-center justify-between gap-3 py-5 text-left font-label text-xs font-semibold uppercase leading-6 tracking-[1px] text-faded-olive transition hover:text-deep-moss focus-visible:rounded-md culvers-focus-ring-compact"
      @click="{{ $group_toggle_var }} = ! {{ $group_toggle_var }}"
      :aria-expanded="{{ $group_toggle_var }}.toString()"
      aria-controls="{{ esc_attr($group_panel_id) }}">
      <span>{{ esc_html($group_label) }}</span>
      <span class="flex size-[15px] shrink-0 items-center justify-center leading-none text-deep-moss tabular-nums" aria-hidden="true" x-text="{{ $group_toggle_var }} ? '−' : '+'"></span>
    </button>
  </div>

  <ul
    id="{{ esc_attr($group_panel_id) }}"
    class="directory-archive__filter-list flex flex-col gap-3 pb-6 pt-7"
    role="radiogroup"
    aria-label="{{ esc_attr($group_aria_label) }}"
    x-show="{{ $group_toggle_var }}"
    x-transition.opacity.duration.150ms>
    <li>
      <button
        type="button"
        role="radio"
        class="directory-archive__filter-option flex w-full min-h-6 items-center gap-[13px] py-0 text-left leading-6 focus-visible:rounded-md culvers-focus-ring-compact"
        :class="{{ $group_state_var }} === '' ? 'directory-archive__filter-option--on' : 'directory-archive__filter-option--off'"
        :aria-checked="{{ $group_state_var }} === ''"
        @click="{{ $group_setter }}('')">
        <span class="directory-archive__radio" :class="{{ $group_state_var }} === '' ? 'directory-archive__radio--checked' : ''" aria-hidden="true"></span>
        <span>{{ esc_html($group_all_label) }}</span>
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
          class="directory-archive__filter-option flex w-full min-h-6 items-center gap-[13px] py-0 text-left leading-6 focus-visible:rounded-md culvers-focus-ring-compact"
          :class="{{ $group_state_var }} === {{ e($slug_json) }} ? 'directory-archive__filter-option--on' : 'directory-archive__filter-option--off'"
          :aria-checked="{{ $group_state_var }} === {{ e($slug_json) }}"
          @click="{{ $group_setter }}({{ e($slug_json) }})">
          <span class="directory-archive__radio" :class="{{ $group_state_var }} === {{ e($slug_json) }} ? 'directory-archive__radio--checked' : ''" aria-hidden="true"></span>
          <span>{{ esc_html($opt_name) }}</span>
        </button>
      </li>
    @endforeach
  </ul>
</div>
