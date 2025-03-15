<script setup>
import { Link } from '@inertiajs/vue3';
import { Pencil } from 'lucide-vue-next';

defineProps({
  data: Object,
});
</script>

<template>
  <div class="mb-4 grid grid-cols-2 gap-4">
    <!-- gender -->
    <div>
      <div class="pb-1 mb-2 items-center justify-between border-b border-gray-200 dark:border-gray-700 flex">
        <div class="text-xs">{{ $t('Gender') }}</div>
        <Link :href="data.url.edit" class="relative">
          <Pencil class="h-3 w-3 text-gray-400" />
        </Link>
      </div>

      <p v-if="data.gender" class="text-sm">
        {{ data.gender }}
      </p>
      <p v-else class="text-sm text-gray-600 dark:text-gray-400">{{ $t('Not set') }}</p>
    </div>

    <!-- pronoun -->
    <div>
      <div class="pb-1 mb-2 items-center justify-between border-b border-gray-200 dark:border-gray-700 flex">
        <div class="text-xs">{{ $t('Pronoun') }}</div>
        <Link :href="data.url.edit" class="relative">
          <Pencil class="h-3 w-3 text-gray-400" />
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
