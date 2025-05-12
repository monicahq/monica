<script setup>
import { Link, router } from '@inertiajs/vue3';

defineProps({
  links: Object,
});

const navigateToSelected = (event) => {
  router.visit(event.target.value);
};
</script>

<template>
  <div>
    <!-- vault sub menu on desktop -->
    <nav class="hidden sm:block bg-white dark:border-slate-300/10 dark:bg-gray-900 sm:border-b sm:border-gray-300">
      <div class="max-w-8xl mx-auto px-4 py-2 sm:px-6 block">
        <ul class="list-none text-sm font-medium">
          <li v-for="link in links" :key="link.url" class="inline">
            <Link
              :href="link.url"
              :class="
                link.selected
                  ? 'bg-blue-700 text-white dark:bg-blue-300 dark:text-gray-900'
                  : 'dark:bg-sky-400/20  dark:text-slate-400'
              "
              class="me-2 rounded-md px-2 py-1 hover:bg-gray-700 hover:text-white dark:hover:text-slate-300">
              {{ link.title }}
            </Link>
          </li>
        </ul>
      </div>
    </nav>

    <!-- vault sub menu on mobile -->
    <nav class="block md:hidden px-4 py-2">
      <div class="relative">
        <select
          @change="navigateToSelected"
          class="w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:outline-hidden focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
          <option value="" disabled>{{ $t('Select a page') }}</option>
          <option v-for="link in links" :key="link.url" :value="link.url" :selected="link.selected">
            {{ link.title }}
          </option>
        </select>
      </div>
    </nav>
  </div>
</template>
