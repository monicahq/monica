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
    <p v-if="errors[0] != 'The given data was invalid.'">
      {{ errors[0] }}
    </p>
    <template v-if="display(errors[1])">
      <ul v-for="errorsList in errors[1]" :key="errorsList.id">
        <li v-for="error in errorsList" :key="error.id">
          {{ error }}
        </li>
      </ul>
    </template>
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

  methods: {
    display($val) {
      return _.isObject($val);
    },
  }
};
</script>
