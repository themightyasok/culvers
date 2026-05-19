@php
  use App\Helpers\Component;
  use App\Helpers\Image;
  use App\Helpers\LayoutShell;

  /**
   * Leasing lettings trio — Figma 51:6524–51:6527 (logo stack, Canela 32 title, Book 24 / lh 30 contact).
   *
   * @var array<string, mixed> $component
   */

  $c = is_array($component ?? null) ? $component : [];
  $root = Component::rootClasses($c);
  $headingTag = Component::headingTagFromComponent($c, 'agents_heading_level', 2);

  $heading = trim((string) ($c['agents_heading'] ?? ''));
  if ($heading === '') {
      $heading = __('Lettings', 'culvers');
  }

  $introRaw = trim((string) ($c['agents_intro'] ?? ''));
  /** @var list<array<string, mixed>> $rows */
  $rows = [];
  $rawAgents = $c['leasing_agents'] ?? null;
  if (is_array($rawAgents)) {
      foreach ($rawAgents as $row) {
          if (is_array($row)) {
              $rows[] = $row;
          }
      }
  }

  $agents = [];
  foreach ($rows as $row) {
      $name = trim((string) ($row['agent_name'] ?? ''));
      $phone = trim((string) ($row['agent_phone'] ?? ''));
      $websiteUrl = trim((string) ($row['agent_website_url'] ?? ''));
      $logo = isset($row['agent_logo']) && is_array($row['agent_logo']) ? $row['agent_logo'] : null;
      if ($name === '' && $phone === '' && $websiteUrl === '') {
          continue;
      }
      if ($name === '') {
          continue;
      }

      $websiteLabel = trim((string) ($row['agent_website_label'] ?? ''));
      if ($websiteUrl !== '' && $websiteLabel === '') {
          $host = wp_parse_url($websiteUrl, PHP_URL_HOST);
          $websiteLabel = is_string($host) ? $host : $websiteUrl;
      }

      $agents[] = [
          'logo' => $logo,
          'name' => $name,
          'phone' => $phone,
          'website_url' => $websiteUrl,
          'website_label' => $websiteLabel,
      ];
  }

  $count = count($agents);
  $gridCols = match ($count) {
      1 => 'lg:grid-cols-1',
      2 => 'lg:grid-cols-2',
      default => 'lg:grid-cols-3',
  };
@endphp

@if($count > 0)
  <section
    class="leasing-agent-grid {{ esc_attr($root) }} bg-lighter-cream py-12 text-deep-moss lg:py-16"
    data-component-root
    data-leasing-agent-grid>
    <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
      <header class="mx-auto max-w-[802px] text-center">
        {{-- Desktop H2 Title (Figma) — reuse section heading ladder. --}}
        <{{ $headingTag }} class="leasing-agent-grid__heading {{ Component::sectionHeadingClasses('text-faded-olive') }}">
          {{ esc_html($heading) }}
        </{{ $headingTag }}>

        @if($introRaw !== '')
          <div
            class="mx-auto mt-10 max-w-[53rem] text-center font-sans text-xl font-light leading-[1.3] text-deep-moss [&_p+p]:mt-4">
            {!! wp_kses_post(wpautop($introRaw)) !!}
          </div>
        @endif
      </header>

      <div
        class="{{ esc_attr(trim('mt-11 grid gap-10 divide-y divide-faded-olive/15 ' . $gridCols . ' lg:gap-0 lg:divide-x lg:divide-y-0')) }}">
        @foreach ($agents as $agent)
          <div class="flex flex-col items-center px-6 pt-10 text-center first:pt-0 lg:px-10 lg:pt-0 xl:px-12">
            @php
              $logo = isset($agent['logo']) && is_array($agent['logo']) ? $agent['logo'] : null;
              $logoUrl = isset($logo['url']) ? (string) $logo['url'] : '';
            @endphp
            @if($logoUrl !== '')
              <div class="flex h-[39px] w-[150px] shrink-0 items-center justify-center">
                {!! Image::render($logo, [
                    'class' => 'max-h-[39px] max-w-[150px] w-auto object-contain',
                    'alt' => $agent['name'],
                ]) !!}
              </div>
            @endif

            <p class="mt-[22px] font-heading text-3xl leading-[1.1] text-faded-olive">
              {{ esc_html($agent['name']) }}
            </p>

            @if($agent['phone'] !== '' || $agent['website_url'] !== '')
              @php $telHref = preg_replace('/[^0-9+]/', '', str_replace("\xc2\xa0", ' ', (string) $agent['phone'])); @endphp
              <p class="mt-[22px] font-sans text-2xl font-light leading-[30px] text-faded-olive">
                @if($agent['phone'] !== '')
                  @if($telHref !== '')
                    <a
                      class="text-faded-olive underline decoration-brand-500 underline-offset-4 hover:decoration-faded-olive"
                      href="{{ esc_url('tel:' . $telHref) }}">{{ esc_html($agent['phone']) }}</a>
                  @else
                    {{ esc_html($agent['phone']) }}
                  @endif
                @endif
                @if($agent['phone'] !== '' && $agent['website_url'] !== '')
                  <br aria-hidden="true" />
                @endif
                @if($agent['website_url'] !== '')
                  <a
                    class="text-faded-olive underline decoration-brand-500 underline-offset-4 hover:decoration-faded-olive"
                    href="{{ esc_url($agent['website_url']) }}"
                    rel="noopener noreferrer"
                    target="_blank">{{ esc_html($agent['website_label']) }}</a>
                @endif
              </p>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  </section>
@elseif(current_user_can('edit_posts'))
  @include('partials.component-editor-placeholder', [
      'wrapperClasses' => $root,
      'message' => __('Add at least one agent row with a name.', 'culvers'),
  ])
@endif
