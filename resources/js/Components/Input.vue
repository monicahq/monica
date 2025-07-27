<script setup>
import { nextTick, onMounted, useTemplateRef } from 'vue';

defineProps({
  modelValue: String,
});

defineEmits(['update:modelValue']);

const input = useTemplateRef('input');
const focus = () => nextTick().then(() => input.value.focus());

onMounted(() => {
  if (input.value.hasAttribute('autofocus')) {
    focus();
  }
});

defineExpose({ focus: focus });
</script>

<template>
  <input
    ref="input"
    class="rounded-md border-gray-300 bg-white shadow-sm focus:border-gray-400 focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-gray-600 dark:focus:ring-gray-800"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
