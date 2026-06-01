@php
  /**
   * Chronological card grid shell for Latest Events / Offers / News archives.
   *
   * @var string $introHtml
   * @var int $foundCount
   * @var string $emptyMessage
   * @var string $cardPartial  e.g. partials.directory-event-card
   * @var bool $showWhatsOnCta
   */
  use App\Helpers\LayoutShell;

  $foundCount = (int) ($foundCount ?? 0);
  $emptyMessage = (string) ($emptyMessage ?? '');
  $cardPartial = (string) ($cardPartial ?? 'partials.directory-event-card');
  $showWhatsOnCta = ($showWhatsOnCta ?? true) === true;
@endphp

<section class="directory-archive-chronological pb-16 md:pb-28">
  <div class="{{ LayoutShell::INNER_MAX_GUTTERED }}">
    @include('partials.directory-archive-intro', ['introHtml' => $introHtml ?? ''])

    <div>
      @if($foundCount <= 0)
        <p class="rounded-[11px] border border-light-brown/25 bg-white px-6 py-12 text-center font-sans text-xl text-faded-olive">
          {{ esc_html($emptyMessage) }}
        </p>
      @else
        <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          @while (have_posts())
            @php the_post(); @endphp
            <li class="min-w-0">
              @include($cardPartial)
            </li>
          @endwhile
        </ul>
      @endif

      @if($showWhatsOnCta)
        @include('partials.whats-on-return-cta')
      @endif
    </div>
  </div>
</section>
