<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const errors = computed(() => usePage().props.errors);
const hasErrors = computed(() => Object.keys(errors.value).length > 0);
</script>

<template>
  <div v-if="hasErrors">
    <div class="font-medium text-red-600 dark:text-red-400">
      {{ $t('Whoops! Something went wrong.') }}
    </div>

    <ul class="mt-3 list-inside list-disc text-sm text-red-600 dark:text-red-400">
      <li v-for="(error, key) in errors" :key="key">
        {{ error }}
      </li>
    </ul>
  </div>
</template>
