<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/inertia-vue3';

const props = defineProps({
  active: Boolean,
  href: String,
  as: String,
});

const classes = computed(() => {
  return props.active
    ? 'block pl-3 pr-4 py-2 border-l-4 border-indigo-400 dark:border-indigo-600 text-base font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900 focus:outline-none focus:text-indigo-800 focus:dark:text-indigo-200 focus:bg-indigo-100 focus:dark:bg-indigo-900 focus:border-indigo-700 focus:dark:border-indigo-300 transition'
    : 'block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 hover:dark:text-gray-200 hover:bg-gray-50 hover:dark:bg-gray-900 hover:border-gray-300 hover:dark:border-gray-700 focus:outline-none focus:text-gray-800 focus:dark:text-gray-200 focus:bg-gray-50 focus:dark:bg-gray-900 focus:border-gray-300 focus:dark:border-gray-700 transition';
});
</script>

<template>
  <div>
    <button v-if="as == 'button'" :class="classes" class="w-full text-left">
      <slot />
    </button>

    <Link v-else :href="href" :class="classes">
      <slot />
    </Link>
  </div>
</template>
