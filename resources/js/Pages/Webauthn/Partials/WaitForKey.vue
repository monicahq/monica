<script setup>
import JetButton from '@/Components/Button.vue';
import { KeyRound, LoaderCircle, TriangleAlert } from 'lucide-vue-next';

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
        class="relative rounded-sm border border-red-400/30 bg-red-100/10 px-4 py-3 dark:border-red-600/30 dark:bg-red-900/10"
        role="alert">
        <span class="flex font-bold text-red-700/80 dark:text-red-300/80">
          <TriangleAlert class="me-2" /> {{ errorMessage }}
        </span>
      </div>
      <JetButton class="mt-4" @click="$emit('retry')"> <KeyRound class="me-2" />{{ $t('Retry') }} </JetButton>
    </div>
    <template v-else>
      <div
        v-if="form.processing"
        class="mb-4 flex rounded-lg border px-4 py-8 shadow-md bg-gray-500/30 border-gray-300 dark:border-gray-700"
        role="alert">
        <div class="me-2">
          <LoaderCircle class="h-5 w-5 animate-spin text-teal-800 dark:text-teal-200" :size="25" />
        </div>
        <p class="font-bold">
          {{ $t('Validating key…') }}
        </p>
      </div>
      <div
        v-else-if="!form.hasErrors"
        class="mb-4 flex rounded-lg border px-4 py-8 shadow-md bg-gray-500/30 border-gray-300 dark:border-gray-700">
        <div class="me-2">
          <LoaderCircle class="h-5 w-5 animate-spin text-teal-800 dark:text-teal-200" :size="25" />
        </div>
        <p class="font-bold">
          {{ $t('Waiting for input from browser interaction…') }}
        </p>
      </div>
    </template>
  </div>
</template>
