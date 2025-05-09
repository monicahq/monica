<script setup>
import JetSectionTitle from './SectionTitle.vue';

defineProps({
  danger: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <div>
    <JetSectionTitle :edit-mode="true">
      <template #title>
        <slot name="title" />
      </template>
      <template #description>
        <slot name="description" />
      </template>
      <template #icon>
        <slot name="icon" />
      </template>
      <template #help>
        <slot name="help" />
      </template>
    </JetSectionTitle>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <slot name="content" />
      </div>
    </div>
  </div>
</template>
