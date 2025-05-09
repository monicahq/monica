<template>
  <button
    :class="'cursor-pointer rounded-md border border-indigo-700 bg-indigo-500 px-3 py-1 font-semibold text-white shadow-xs hover:bg-indigo-700 dark:hover:bg-indigo-400'"
    :disabled="state === 'loading' || state === 'disabled'"
    type="submit">
    <span v-if="state === 'loading'"> {{ $t('Loading…') }} </span>

    <span v-if="state !== 'loading'">
      {{ text }}
    </span>
  </button>
</template>

<script>
export default {
  props: {
    text: {
      type: String,
      default: '',
    },
    state: {
      type: String,
      default: '',
    },
  },
};
</script>
