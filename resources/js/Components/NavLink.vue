<template>
  <inertia-link :href="href" :class="classes">
    <slot />
  </inertia-link>
</template>

<script>
export default {
  props: {
    href: {
      type: String,
      default: null,
    },
    active: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    classes() {
      return this.active
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 dark:border-indigo-600 text-sm font-medium leading-5 text-gray-900 dark:text-gray-100 focus:outline-none focus:border-indigo-700 focus:dark:border-indigo-300 transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:dark:text-gray-300 hover:border-gray-300 hover:dark:border-gray-700 focus:outline-none focus:text-gray-700 focus:dark:text-gray-300 focus:border-gray-300 focus:dark:border-gray-700 transition duration-150 ease-in-out';
    },
  },
};
</script>
