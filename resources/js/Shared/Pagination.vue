<style lang="scss" scoped></style>

<template>
  <div class="flex justify-between text-center">
    <inertia-link
      v-show="paginator.previousPageUrl"
      class="rounded border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500"
      :href="paginator.previousPageUrl"
      title="Previous">
      &larr; {{ $t('app.previous') }}
    </inertia-link>
    <inertia-link
      v-show="paginator.nextPageUrl"
      class="rounded border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500"
      :href="paginator.nextPageUrl"
      title="Next">
      {{ $t('app.next') }} &rarr;
    </inertia-link>
  </div>
</template>

<script>
export default {
  props: {
    paginator: {
      type: Object,
      default: null,
    },
  },
};
</script>
