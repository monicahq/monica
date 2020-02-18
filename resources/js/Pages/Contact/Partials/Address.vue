<style lang="scss" scoped>
</style>

<template>
  <!-- list of addresses -->
  <div class="pa2 bb">

    <!-- non-edit mode -->
    <template v-if="!editMode">
      <p class="mb1 f6 mt0">
        {{ $t('people.contact_address_title') }}
        <span class="fr pointer bb b--dotted bt-0 bl-0 br-0" @click.prevent="editMode = true">{{ $t('app.edit') }}</span>
      </p>

      <!-- list of addresses -->
      <ul>
        <li v-for="address in contact.addresses" :key="address.id">

        </li>
      </ul>
    </template>

    <!-- form to edit the work information -->
    <template v-if="editMode">
      <form @submit.prevent="submit">
        <errors :errors="form.errors" :classes="'mb3'" />

        <form-input
          v-model="form.title"
          v-on:escape="editMode = false"
          :id="'title'"
          :input-type="'text'"
          :required="false"
          :custom-class="'br2 f5 ba b--black-40 pa2 outline-0'"
          :title="$t('people.work_edit_job')">
        </form-input>

        <form-input
          v-model="form.companyName"
          v-on:escape="editMode = false"
          :id="'companyName'"
          :input-type="'text'"
          :required="false"
          :custom-class="'br2 f5 ba b--black-40 pa2 outline-0'"
          :title="$t('people.work_edit_company')">
        </form-input>

        <!-- Actions -->
        <div class="">
          <div class="flex-ns justify-between">
            <div>
            </div>
            <div class="">
              <a class="btn dib tc w-auto-ns w-100 mb2 pv2 ph3 pointer" data-cy="cancel-add-work-information" @click="editMode = false">{{ $t('app.cancel') }}</a>
              <loading-button :classes="'btn add w-auto-ns w-100 mb2 pv2 ph3'" :state="loadingState" :text="$t('app.save')" :cypress-selector="'submit-add-work-information'" />
            </div>
          </div>
        </div>
      </form>
    </template>

  </div>
</template>

<script>
import FormInput from '@/Shared/Input';
import LoadingButton from '@/Shared/LoadingButton';
import Errors from '@/Shared/Errors';

export default {
  components: {
    FormInput,
    LoadingButton,
    Errors
  },

  props: {
    contact: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      editMode: false,
      localDescription: '',
      form: {
        title: null,
        companyName: null,
        errors: [],
      },
      loadingState: '',
    };
  },

  created: function() {
    this.localDescription = this.contact.work.description;
    this.form.title = this.contact.work.title;
    this.form.companyName = this.contact.work.companyName;
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios.post('/people/' + this.contact.hash + '/work', this.form)
        .then(response => {
          flash(this.$t('people.work_edit_success'), 'success');
          this.localDescription = response.data.data.description;
          this.form.title = response.data.data.title;
          this.form.companyName = response.data.data.company;
          this.editMode = false;
          this.loadingState = null;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = _.flatten(_.toArray(error.response.data));
        });
    },
  }
};
</script>
