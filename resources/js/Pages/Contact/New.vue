<style lang="scss" scoped>
.contacts {
  li:last-child {
    border-bottom: 0;
  }
}
</style>

<template>
  <layout title="Add a new contact">

    <!-- breadcrumb -->
    <section class="w-100 ph3 ph5-ns bb">
      <div class="db mw9 center w-100 breadcrumb f6">
        <ul class="list ph0 tl db mh0 mv2">
          <li class="di">
            <inertia-link :href="'/dashboard'">{{ $t('app.breadcrumb_dashboard') }}</inertia-link>
          </li>
          <li class="di">
            <inertia-link :href="'/people'">{{ $t('app.breadcrumb_list_contacts') }}</inertia-link>
          </li>
          <li class="di">
            {{ $t('app.breadcrumb_contact_new') }}
          </li>
        </ul>
      </div>
    </section>

    <!-- form to create a contact -->
    <section class="ph3 ph0-ns">
      <div class="mt4 mw7 center mb3">
        <h2 class="f3 fw5 ma0">{{ $t('people.people_add_title') }}</h2>
      </div>

      <div class="mw7 center br3 ba bg-white mb6">

        <form @submit.prevent="submit">

          <div class="pa4-ns ph3 pv2 bb">
            <errors :errors="form.errors" :classes="'mb3'" />

            <div class="mb3">
              <form-input
                v-model="form.firstname"
                :id="'first_name'"
                :type="'text'"
                :required="true"
                :label-class="'db mb2'"
                :input-class="'w-100'"
                :title="$t('people.people_add_firstname')">
              </form-input>
            </div>

            <div class="mb3">
              <form-input
                v-model="form.lastname"
                :id="'last_name'"
                :type="'text'"
                :required="false"
                :label-class="'db mb2'"
                :input-class="'w-100'"
                :title="$t('people.people_add_lastname')">
              </form-input>
            </div>

            <div class="mb3 mb0-ns">
              <form-input
                v-model="form.nickname"
                :id="'nickname'"
                :type="'text'"
                :required="false"
                :label-class="'db mb2'"
                :input-class="'w-100'"
                :title="$t('people.people_add_nickname')">
              </form-input>
            </div>
          </div>

          <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb">
            <form-select
              :required="false"
              :title="$t('people.people_add_gender')"
              :id="'gender'"
              v-model="form.gender"
              :options="genders">
            </form-select>
          </div>

          <div class="ph4-ns ph3 pv3 flex-ns justify-between">
            <div>
              <button class="btn w-auto-ns w-100 pb0-ns" name="save_and_add_another" type="submit">{{ $t('people.people_save_and_add_another_cta') }}</button>
              <button class="btn w-auto-ns w-100 pb0-ns" name="save" value="true" type="submit">{{ $t('people.people_add_cta') }}</button>
            </div>
            <div>
              <inertia-link href="/people" class="btn w-auto-ns w-100 pb0-ns">{{ $t('app.cancel') }}</inertia-link>
            </div>
          </div>

        </form>
      </div>
    </section>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import FormInput from '@/Shared/Input';
import FormSelect from '@/Shared/Select';
import Errors from '@/Shared/Errors';

export default {
  components: {
    Layout,
    FormInput,
    FormSelect,
    Errors
  },

  props: {
    genders: {
      type: Array,
      default: null,
    },
  },

  data() {
    return {
      form: {
        firstname: null,
        lastname: null,
        nickname: null,
        gender: null,
        errors: [],
      },
      loadingState: '',
    };
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios.post('/people', this.form)
        .then(response => {
          localStorage.success = this.$t('account.company_news_update_success');
          this.$inertia.visit(response.data.data.url);
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = _.flatten(_.toArray(error.response.data));
        });
    },
  }
};
</script>
