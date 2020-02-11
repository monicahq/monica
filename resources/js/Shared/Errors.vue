<style lang="scss" scoped>
.border-red {
  background-color: #fff5f5;
  border-color: #fc8181;
  color: #c53030;
}
</style>

<template>
  <div v-if="errors.length > 0" class="border-red ba br3 pa3" :class="classes">
    <p class="mv0 fw6">{{ $t('app.error_title') }}</p>
    <p class="mb0">{{ errors[0] }}</p>
  </div>
</template>

<script>
export default {
  props: {
    errors: {
      type: Array,
      default: () => [],
    },
    classes: {
      type: String,
      default: '',
    }
  },
};
</script>
