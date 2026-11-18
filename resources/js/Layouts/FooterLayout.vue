<template>
  <footer class="mb-8 mt-4 text-center text-xs dark:text-gray-100">
    <div class="mb-2">
      BlackBookd · based on Monica (AGPL-3.0) · {{ new Date().getFullYear().toString() }}
      {{ $t('Made from all over the world. We ❤️ you.') }}
    </div>
    <div>
      {{ $t('This site is open source.') }}
      <span v-html="$page.props.footer"></span>
    </div>
  </footer>
</template>
