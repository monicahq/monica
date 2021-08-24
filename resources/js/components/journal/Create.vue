<style scoped>
</style>

<template>
<div class="mw8 center mb6 cf">
  <div class="fl w-70 pa4 br3 ba b--gray-monica bg-white bb b--gray-monica">
    <form @submit.prevent="submit()">
      <form-textarea
        id="description"
        :required="false"
        :label="'Write about your day or anything that is on your mind.'"
        :rows="30"
        :placeholder="'s'"
        class="mb3"
        :supports-markdown="true"
        v-model="form.post"
      >
      </form-textarea>

      <form-input id="'redirect-url'" v-model="form.title" :iclass="'db br2 f5 w-50 ba b--black-40 pa2 outline-0 w-100'" :required="false" :title="'Add a title'" />

      <div class="form-group actions">
        <button type="submit" class="btn btn-primary">{{ $t('journal.journal_add_cta') }}</button>
        <a href="#" class="btn btn-secondary">{{ $t('app.cancel') }}</a>
      </div>
    </form>
  </div>

  <div class="fl w-30 pl4">
    <p class="mb1">Date of the entry</p>
    <form-date
      :id="'date'"
      :default-date="''"
      v-model="form.date"
      :locale="'en'">
    </form-date>
  </div>
</div>
</template>

<script>
export default {
  data() {
    return {
      form: {
        errors: [],
        post: null,
        title: null,
        date: null,
      },
    };
  },

  methods: {
    submit() {
      axios.post('journal/create', this.form)
        .then(response => {

        });
    }
  }
};
</script>
