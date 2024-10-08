<script setup>
import { Link } from '@inertiajs/vue3';
import EditIcon from '@/Shared/Icons/EditIcon.vue';

defineProps({
  data: Object,
});
</script>

<template>
  <div class="mb-4 grid grid-cols-2 gap-4">
    <!-- gender -->
    <div>
      <div class="mb-3 items-center justify-between border-b border-gray-200 dark:border-gray-700 sm:flex">
        <div class="mb-2 text-xs sm:mb-0">{{ $t('Gender') }}</div>
        <Link :href="data.url.edit" class="relative">
          <EditIcon />
        </Link>
      </div>

      <p v-if="data.gender" class="text-sm">
        {{ data.gender }}
      </p>
      <p v-else class="text-sm text-gray-600 dark:text-gray-400">{{ $t('Not set') }}</p>
    </div>

    <!-- pronoun -->
    <div>
      <div class="mb-3 items-center justify-between border-b border-gray-200 dark:border-gray-700 sm:flex">
        <div class="mb-2 text-xs sm:mb-0">{{ $t('Pronoun') }}</div>
        <Link :href="data.url.edit" class="relative">
          <EditIcon />
        </Link>
      </div>

      <p v-if="data.pronoun" class="text-sm">
        {{ data.pronoun }}
      </p>
      <p v-else class="text-sm text-gray-600 dark:text-gray-400">{{ $t('Not set') }}</p>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.icon-sidebar {
  top: -2px;
}
</style>
