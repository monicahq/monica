<script setup>
import PrettyButton from '@/Shared/Form/PrettyButton.vue';

defineEmits(['edit']);

defineProps({
  editMode: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <div>
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 flex font-semibold sm:mb-0">
        <span v-if="$slots.icon" class="me-1">
          <slot name="icon" />
        </span>
        <span class="me-2">
          <slot name="title" />
        </span>

        <slot name="help" />
      </h3>

      <PrettyButton v-if="!editMode" :text="$t('Edit')" @click="$emit('edit')" />
    </div>

    <!-- help text -->
    <div
      v-if="$slots.description"
      class="mb-6 flex items-center rounded-xs border bg-slate-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-slate-900">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 flex-none pe-2"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>

      <div class="flex-auto">
        <p>
          <slot name="description" />
        </p>
      </div>
    </div>
  </div>
</template>
