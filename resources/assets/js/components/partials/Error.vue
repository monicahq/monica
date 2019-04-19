<template>
  <div v-if="errors.length > 0" class="alert alert-danger">
    <p>{{ $t('app.error_title') }}</p>
    <br />
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
  </div>
</template>

<script>
export default {
  props: {
    errors: {
      type: Array,
      default: function () {
        return [];
      }
    },
  },
  methods: {
    display($val) {
      return _.isObject($val);
    },
  }
};
</script>
