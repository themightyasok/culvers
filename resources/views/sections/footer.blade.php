<footer class="border-t border-zinc-800 bg-zinc-950 py-12 text-sm text-zinc-400">
  <div class="mx-auto flex max-w-[1800px] flex-col gap-6 px-4 lg:flex-row lg:items-center lg:justify-between lg:px-8">
    <p>&copy; {{ wp_date('Y') }} {{ get_bloginfo('name') }}</p>
    @if(has_nav_menu('footer_navigation'))
      <nav aria-label="{{ esc_attr__('Footer', 'culvers') }}">
        {!! wp_nav_menu([
          'theme_location' => 'footer_navigation',
          'container' => false,
          'menu_class' => 'flex flex-wrap gap-4',
          'fallback_cb' => false,
          'echo' => false,
        ]) !!}
      </nav>
    @endif
  </div>
</footer>
