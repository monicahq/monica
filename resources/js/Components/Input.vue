<template>
  <input
    ref="input"
    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
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
