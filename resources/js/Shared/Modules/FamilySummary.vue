<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  data: Object,
});
</script>

<template>
  <div v-if="data.love_relationships.length !== 0 || data.family_relationships.length !== 0" class="mb-4">
    <div class="mb-3 items-center justify-between border-b border-gray-200 dark:border-gray-700 sm:flex">
      <div class="mb-1 text-xs">{{ $t('Family summary') }}</div>
    </div>

    <!-- love relationships -->
    <div v-if="data.love_relationships.length > 0" class="flex">
      <span class="me-2 inline text-sm"> ❤️ </span>
      <ul>
        <li v-for="relationship in data.love_relationships" :key="relationship.id" class="me-2 inline">
          <!-- name -->
          <Link
            v-if="relationship.contact.url.show"
            :href="relationship.contact.url.show"
            class="text-blue-500 hover:underline">
            {{ relationship.contact.name }}
          </Link>
          <span v-else>{{ relationship.contact.name }}</span>

          <!-- age -->
          <span v-if="relationship.contact.age" class="ms-2 text-xs text-gray-400"
            >({{ relationship.contact.age }})</span
          >
        </li>
      </ul>
    </div>

    <!-- family relationships -->
    <div v-if="data.family_relationships.length > 0" class="flex">
      <span class="me-2 inline text-sm"> 👪 </span>
      <ul>
        <li v-for="relationship in data.family_relationships" :key="relationship.id" class="me-2 inline">
          <!-- name -->
          <Link
            v-if="relationship.contact.url.show"
            :href="relationship.contact.url.show"
            class="text-blue-500 hover:underline">
            {{ relationship.contact.name }}
          </Link>
          <span v-else>{{ relationship.contact.name }}</span>

          <!-- age -->
          <span v-if="relationship.contact.age" class="ms-2 text-xs text-gray-400"
            >({{ relationship.contact.age }})</span
          >
        </li>
      </ul>
    </div>
  </div>
</template>
