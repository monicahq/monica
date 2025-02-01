<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  href: String,
  as: String,
});
</script>

<template>
  <div>
    <button
      v-if="as === 'button'"
      type="submit"
      class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 transition hover:bg-gray-100 focus:bg-gray-100 focus:outline-hidden dark:text-gray-300 dark:hover:bg-gray-900">
      <slot />
    </button>

    <a
      v-else-if="as === 'a'"
      :href="href"
      class="block px-4 py-2 text-sm leading-5 text-gray-700 transition hover:bg-gray-100 focus:bg-gray-100 focus:outline-hidden dark:text-gray-300 dark:hover:bg-gray-900">
      <slot />
    </a>

    <Link
      v-else
      :href="href"
      class="block px-4 py-2 text-sm leading-5 text-gray-700 transition hover:bg-gray-100 focus:bg-gray-100 focus:outline-hidden dark:text-gray-300 dark:hover:bg-gray-900">
      <slot />
    </Link>
  </div>
</template>
