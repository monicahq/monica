<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  active: Boolean,
  href: String,
  as: String,
});

const classes = computed(() => {
  return props.active
    ? 'block ps-3 pe-4 py-2 border-s-4 border-indigo-400 dark:border-indigo-600 text-base font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900 focus:outline-hidden focus:text-indigo-800 dark:focus:text-indigo-200 focus:bg-indigo-100 dark:focus:bg-indigo-900 focus:border-indigo-700 dark:focus:border-indigo-300 transition'
    : 'block ps-3 pe-4 py-2 border-s-4 border-transparent text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-900 hover:border-gray-300 dark:hover:border-gray-700 focus:outline-hidden focus:text-gray-800 dark:focus:text-gray-200 focus:bg-gray-50 dark:focus:bg-gray-900 focus:border-gray-300 dark:focus:border-gray-700 transition';
});
</script>

<template>
  <div>
    <button v-if="as === 'button'" :class="classes" class="w-full text-left">
      <slot />
    </button>

    <Link v-else :href="href" :class="classes">
      <slot />
    </Link>
  </div>
</template>
