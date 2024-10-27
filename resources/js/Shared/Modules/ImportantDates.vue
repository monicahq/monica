<script setup>
import { Link } from '@inertiajs/vue3';
import EditIcon from '@/Shared/Icons/EditIcon.vue';

defineProps({
  data: Object,
});
</script>

<template>
  <div class="mb-4">
    <div class="mb-3 items-center justify-between border-b border-gray-200 dark:border-gray-700 flex">
      <div class="mb-2 text-xs sm:mb-0">{{ $t('Important dates') }}</div>
      <Link :href="data.url.edit" class="relative">
        <EditIcon />
      </Link>
    </div>

    <!-- list of dates -->
    <ul v-for="date in data.dates" :key="date.id">
      <li class="mb-1 flex items-center justify-between hover:bg-slate-50 dark:bg-slate-800 hover:dark:bg-slate-900">
        <span class="text-sm">{{ date.label }}</span>
        <span class="text-sm">
          {{ date.date }} <span v-if="date.age" class="text-xs text-gray-400">({{ date.age }})</span>
        </span>
      </li>
    </ul>

    <!-- blank state -->
    <p v-if="data.dates.length === 0" class="text-sm text-gray-600 dark:text-gray-400">{{ $t('Not set') }}</p>
  </div>
</template>

<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}
</style>
