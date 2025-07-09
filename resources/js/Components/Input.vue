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
    class="rounded-md border-gray-300 shadow-xs focus:border-indigo-300 focus:ring-3 focus:ring-indigo-200/50 dark:border-gray-600 dark:bg-gray-900 dark:shadow-gray-700 dark:focus:border-indigo-700"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)" />
</template>
