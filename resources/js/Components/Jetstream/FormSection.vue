<script setup>
import { computed, ref, useSlots } from 'vue';
import JetSectionTitle from './SectionTitle.vue';

const emit = defineEmits(['submitted']);
const props = defineProps({
  useEditMode: Boolean,
});

const hasActions = computed(() => !!useSlots().actions);
const editMode = ref(!props.useEditMode);

const submit = () => {
  emit('submitted');
  if (props.useEditMode) {
    editMode.value = false;
  }
};
</script>

<template>
  <div>
    <JetSectionTitle :edit-mode="editMode" @edit="editMode = true">
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

    <!-- normal mode -->
    <div v-if="!editMode" class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <slot name="content" />
    </div>

    <!-- edit mode -->
    <form
      v-if="editMode"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit">
      <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <slot name="form" />
      </div>

      <div
        v-if="hasActions"
        class="flex items-center justify-end bg-gray-50 px-4 py-3 text-right shadow-xs dark:bg-gray-900 dark:shadow-gray-700 sm:rounded-bl-md sm:rounded-br-md sm:px-6">
        <slot name="actions" />
      </div>
    </form>
  </div>
</template>
