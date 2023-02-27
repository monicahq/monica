<script setup>
import Avatar from '@/Shared/Avatar.vue';

const props = defineProps({
  data: Object,
  contactViewMode: Boolean,
});
</script>

<template>
  <div class="rounded-lg border border-gray-300 dark:border-gray-700">
    <!-- contact information -->
    <div
      v-if="!contactViewMode"
      class="flex items-center border-b border-gray-300 px-3 py-2 text-sm dark:border-gray-700">
      <Avatar
        :data="props.data.contact.avatar"
        :classes="'rounded-full border-gray-200 dark:border-gray-800 border relative h-5 w-5 mr-2'" />

      <div class="flex flex-col">
        <inertia-link :href="props.data.contact.url" class="text-gray-800 hover:underline dark:text-gray-200">{{
          props.data.contact.name
        }}</inertia-link>
      </div>
    </div>

    <div class="px-3 pt-2 pb-2">
      <!-- the note still exists in the system -->
      <div v-if="props.data.note.object">
        <div v-if="props.data.note.object.title" class="mb-2 block">{{ props.data.note.object.title }}</div>
        <div>{{ props.data.note.object.body }}</div>
      </div>

      <!-- the note was deleted -->
      <span
        v-else
        class="mr-2 mb-2 inline-block rounded bg-neutral-200 py-1 px-2 text-xs font-semibold text-neutral-800 last:mr-0">
        <span>{{ props.data.note.description }}</span>
      </span>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.icon-avatar {
  top: 3px;
  left: -11px;
}
</style>
