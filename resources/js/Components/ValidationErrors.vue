<template>
  <div v-if="hasErrors">
    <div class="font-medium text-red-600 dark:text-red-400">Whoops! Something went wrong.</div>

    <ul class="mt-3 list-inside list-disc text-sm text-red-600 dark:text-red-400">
      <li v-for="(error, key) in errors" :key="key">
        {{ error }}
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  computed: {
    errors() {
      return this.$page.props.errors;
    },

    hasErrors() {
      return Object.keys(this.errors).length > 0;
    },
  },
};
</script>
