<script setup>
import { ref } from 'vue';
import { SquareX } from 'lucide-vue-next';

const props = defineProps({
  beta: Boolean,
});
const betaRef = ref(props.beta);

const closeBeta = () => {
  betaRef.value = false;
  axios.post(route('close_beta'));
};
</script>

<template>
  <div v-if="betaRef" class="relative mb-4 rounded-lg border bg-amber-50 p-6 dark:bg-amber-950">
    <div
      class="absolute top-0 end-0 mt-2 mr-2 cursor-pointer rounded px-1 py-[3px] text-xs hover:bg-amber-200/40 dark:hover:bg-amber-800/25"
      @click="closeBeta"
      :title="$t('Close')">
      <SquareX class="h-5 w-5" />
    </div>
    <p class="mb-2 text-center font-bold">
      <span class="me-2">🚧</span>
      {{ $t('Chandler is in beta.') }}
      <span class="ms-2">🚧</span>
    </p>
    <p class="mb-2">{{ $t('Compared to Monica:') }}</p>
    <ul class="list mb-2 ps-3">
      <li class="list-disc">
        {{ $t('it misses some of the features, the most important ones being the API and gift management,') }}
      </li>
      <li class="list-disc">{{ $t("you can't import any data from your current Monica account(yet),") }}</li>
      <li class="list-disc">{{ $t("you can't even use your current username or password to sign in,") }}</li>
      <li class="list-disc">{{ $t("however, there are many, many new features that didn't exist before.") }}</li>
    </ul>
    <p>{{ $t("We hope you'll like it.") }}</p>
  </div>
</template>
