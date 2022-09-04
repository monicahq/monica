<template>
  <input
    ref="input"
    class="focus:dark:ring-opacity-900 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:border-gray-700 focus:dark:border-indigo-700 focus:dark:ring-indigo-800"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)" />
</template>

<script>
export default {
  props: {
    modelValue: {
      type: String,
      default: '',
    },
  },

  emits: ['update:modelValue'],

  methods: {
    focus() {
      this.$refs.input.focus();
    },
  },
};
</script>
