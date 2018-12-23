<template>
  <div v-if="errors.length > 0" class="alert alert-danger">
    <p>{{ $t('app.error_title') }}</p>
    <br />
    <p>{{ errors[0] }}</p>
    <ul v-for="errorsList in errors[1]" :key="errorsList.id">
      <li v-for="error in errorsList" :key="error.id">
        ▪️ {{ error }}
      </li>
    </ul>
  </div>
</template>

<script>
export default {
    props: {
        errors: {
            type: Object,
            default: null,
        },
    },
};
</script>
