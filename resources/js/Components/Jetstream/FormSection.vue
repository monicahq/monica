<script setup>
import { computed, useSlots } from 'vue';
import JetSectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !!useSlots().actions);
</script>

<template>
  <div class="md:grid md:grid-cols-3 md:gap-6">
    <JetSectionTitle>
      <template #title>
        <slot name="title" />
      </template>
      <template #description>
        <slot name="description" />
      </template>
    </JetSectionTitle>

    <div class="mt-5 md:col-span-2 md:mt-0">
      <form @submit.prevent="$emit('submitted')">
        <div
          class="bg-white px-4 py-5 shadow dark:bg-gray-900 dark:shadow-gray-700 sm:p-6"
          :class="hasActions ? 'sm:rounded-tl-md sm:rounded-tr-md' : 'sm:rounded-md'">
          <div class="grid grid-cols-6 gap-6">
            <slot name="form" />
          </div>
        </div>

        <div
          v-if="hasActions"
          class="flex items-center justify-end bg-gray-50 px-4 py-3 text-right shadow dark:bg-gray-900 dark:shadow-gray-700 sm:rounded-bl-md sm:rounded-br-md sm:px-6">
          <slot name="actions" />
        </div>
      </form>
    </div>
  </div>
</template>
