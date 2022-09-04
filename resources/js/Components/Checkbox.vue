<template>
  <input
    v-model="proxyChecked"
    type="checkbox"
    :value="value"
    class="focus:dark:ring-opacity-900 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-indigo-400 focus:dark:border-indigo-700 focus:dark:ring-indigo-800" />
</template>

<script>
export default {
  props: {
    checked: {
      type: [Array, Boolean],
      default: false,
    },
    value: {
      type: [String, Number],
      default: null,
    },
  },
  emits: ['update:checked'],

  computed: {
    proxyChecked: {
      get() {
        return this.checked;
      },

      set(val) {
        this.$emit('update:checked', val);
      },
    },
  },
};
</script>
