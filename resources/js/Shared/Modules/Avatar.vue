<template>
  <div class="mx-auto mb-6 sm:w-1/2" v-html="data" />
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      default: null,
    },
  },
};
</script>

<style lang="scss" scoped></style>
