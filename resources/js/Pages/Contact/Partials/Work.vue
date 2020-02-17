<style lang="scss" scoped>
</style>

<template>
  <!-- work information -->
  <div class="pa2 bb">

    <!-- non-edit mode -->
    <template v-if="!editMode">
      <p class="mb1 f6 mt0">{{ $t('people.work_information') }} <span class="fr pointer bb b--dotted bt-0 bl-0 br-0" @click.prevent="editMode = true">{{ $t('app.edit') }}</span></p>
      <p class="mv0 lh-copy" v-if="localDescription">{{ localDescription }}</p>
      <p class="mv0 lh-copy" v-else>{{ $t('people.description_nothing_yet') }}</p>
    </template>

    <!-- form to edit the description -->
    <template v-if="editMode">
      <form @submit.prevent="submit">
        <errors :errors="form.errors" :classes="'mb3'" />

        <text-area
          v-model="form.description"
          :label="$t('people.description_title')"
          :datacy="'description-textarea'"
          :required="true"
          :rows="10"
          :help="$t('people.description_title_help')"
        />

        <!-- Actions -->
        <div class="">
          <div class="flex-ns justify-between">
            <div>
              <a v-if="localDescription" class="btn dib tc w-auto-ns w-100 mb2 pv2 ph3" href="#" data-cy="clear-description" @click="clear()">❌ {{ $t('app.clear') }}</a>
            </div>
            <div class="">
              <a class="btn dib tc w-auto-ns w-100 mb2 pv2 ph3 pointer" data-cy="cancel-add-description" @click="editMode = false">{{ $t('app.cancel') }}</a>
              <loading-button :classes="'btn add w-auto-ns w-100 mb2 pv2 ph3'" :state="loadingState" :text="$t('app.save')" :cypress-selector="'submit-add-description'" />
            </div>
          </div>
        </div>
      </form>
    </template>

  </div>
</template>

<script>
import TextArea from '@/Shared/TextArea';
import LoadingButton from '@/Shared/LoadingButton';
import Errors from '@/Shared/Errors';

export default {
  components: {
    TextArea,
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
      localJobTitle: null,
      localCompanyName: null,
      editMode: false,
      form: {
        description: null,
        errors: [],
      },
      loadingState: '',
    };
  },

  created: function() {
    this.localJobTitle = this.contact;
    this.localCompanyName = this.contact;
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios.post('/people/' + this.hash + '/description', this.form)
        .then(response => {
          flash(this.$t('people.description_edit_success'), 'success');
          this.localDescription = response.data.description;
          this.editMode = false;
          this.loadingState = null;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = _.flatten(_.toArray(error.response.data));
        });
    },

    clear() {
      this.loadingState = 'loading';

      axios.delete('/people/' + this.hash + '/description')
        .then(response => {
          flash(this.$t('people.description_edit_success'), 'success');
          this.localDescription = response.data.description;
          this.form.description = response.data.description;
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
