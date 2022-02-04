<style lang="scss" scoped></style>

<template>
  <div class="mb-6" v-html="data" />
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
