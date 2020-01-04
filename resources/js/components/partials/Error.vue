<template>
  <div v-if="apierror || errors.length > 0" class="alert alert-danger">
    <p>{{ $t('app.error_title') }}</p>
    <template v-if="apierror">
      <ul>
        <li v-for="error in errors[0].message" :key="error.id">
          ▪️ {{ error }}
        </li>
      </ul>
    </template>
    <template v-else>
      <p v-if="errors[0] != 'The given data was invalid.'">
        {{ errors[0] }}
      </p>
      <template v-if="display(errors[1])">
        <ul v-for="errorsList in errors[1]" :key="errorsList.id">
          <li v-for="error in errorsList" :key="error.id">
            ▪️ {{ error }}
          </li>
        </ul>
      </template>
    </template>
  </div>
</template>

<script>
export default {
  props: {
    errors: {
      type: [Array, Object],
      default: () => [],
    },
  },
  computed: {
    apierror() {
      return _.isObject(this.errors[0]) && this.errors[0].error_code !== undefined;
    }
  },
  methods: {
    display($val) {
      return _.isObject($val);
    },
  }
};
</script>
