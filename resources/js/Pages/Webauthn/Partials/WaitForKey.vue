<script setup>
import JetButton from '@/Components/Button.vue';

defineProps({
  errorMessage: String,
  form: Object,
});

defineEmits(['retry']);
</script>

<template>
  <div>
    <div v-if="errorMessage !== ''" class="form-error-message mb3">
      <div
        class="relative rounded border border-red-400 bg-red-100 px-4 py-3 dark:border-red-600 dark:bg-red-900"
        role="alert">
        <p class="font-bold text-red-700 dark:text-red-300">
          {{ errorMessage }}
        </p>
        <JetButton class="mt-4" @click="$emit('retry')">
          {{ $t('Retry') }}
        </JetButton>
      </div>
    </div>
    <template v-else>
      <div
        v-if="form.processing"
        class="mb-4 flex rounded-b border-t-4 border-teal-500 bg-teal-100 px-4 py-8 shadow-md dark:bg-teal-900 dark:shadow-gray-700"
        role="alert">
        <div class="mr-2 text-teal-800 dark:text-teal-200">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            stroke="currentColor"
            class="h-8 w-8"
            viewBox="-50 0 700 600">
            <g transform="matrix(42.857142857142854,0,0,42.857142857142854,0,0)">
              <g>
                <polyline points="5.62 7.38 11.5 1.5 13.5 3.5"></polyline>
                <line x1="9.25" y1="3.75" x2="11" y2="5.5"></line>
                <circle cx="3.5" cy="9.5" r="3"></circle>
              </g>
            </g>
          </svg>
        </div>
        <p class="text-2xl font-bold text-teal-900 dark:text-teal-100">
          {{ $t('Validating key…') }}
        </p>
      </div>
      <div
        v-else-if="!form.hasErrors"
        class="mb-4 flex rounded-b border-t-4 border-indigo-500 bg-indigo-100 px-4 py-8 shadow-md dark:bg-indigo-900 dark:shadow-gray-700">
        <div class="mr-2 text-indigo-800 dark:text-indigo-200">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            stroke="currentColor"
            class="h-8 w-8"
            viewBox="-50 0 700 600">
            <g transform="matrix(42.857142857142854,0,0,42.857142857142854,0,0)">
              <g>
                <polyline points="5.62 7.38 11.5 1.5 13.5 3.5"></polyline>
                <line x1="9.25" y1="3.75" x2="11" y2="5.5"></line>
                <circle cx="3.5" cy="9.5" r="3"></circle>
              </g>
            </g>
          </svg>
        </div>
        <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">
          {{ $t('Waiting for key…') }}
        </p>
      </div>
    </template>
  </div>
</template>
