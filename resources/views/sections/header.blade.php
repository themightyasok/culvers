<header class="fixed inset-x-0 top-0 z-50 border-b border-zinc-800/80 bg-zinc-950/90 backdrop-blur">
  <div class="mx-auto flex h-16 max-w-[1800px] items-center justify-between px-4 lg:h-20 lg:px-8">
    <a class="text-lg font-semibold tracking-tight text-white" href="{{ home_url('/') }}">
      {{ get_bloginfo('name') }}
    </a>
    @if(has_nav_menu('primary_navigation'))
      <nav aria-label="{{ esc_attr__('Primary', 'culvers') }}">
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'container' => false,
          'menu_class' => 'flex gap-6 text-sm text-zinc-200',
          'fallback_cb' => false,
          'echo' => false,
        ]) !!}
      </nav>
    @endif
  </div>
</header>
