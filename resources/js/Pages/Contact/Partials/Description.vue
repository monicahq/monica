<style lang="scss" scoped>
.contacts {
  li:last-child {
    border-bottom: 0;
  }
}
</style>

<template>
  <!-- description -->
  <div class="pa2 bb">

    <!-- non-edit mode -->
    <template v-if="!editMode">
      <p class="mb1 f6 mt0">{{ $t('people.information_edit_description') }} <a class="fr pointer" @click="editMode = true">{{ $t('app.edit') }}</a></p>
      <p class="mv0 lh-copy" v-if="localDescription">{{ localDescription }}</p>
      <p class="mv0 lh-copy" v-else>{{ $t('people.information_edit_no_description') }}</p>
    </template>

    <!-- form to edit the description -->
    <template v-if="editMode">
      <form @submit.prevent="submit">
        <errors :errors="form.errors" :classes="'mb3'" />

        <text-area
          v-model="form.description"
          :label="$t('people.information_edit_description')"
          :datacy="'description-textarea'"
          :required="true"
          :rows="10"
          :help="$t('people.information_edit_description_help')"
        />

        <!-- Actions -->
        <div class="">
          <div class="flex-ns justify-between">
            <div>
              <a class="btn dib tc w-auto-ns w-100 mb2 pv2 ph3" data-cy="clear-description" @click="clear()">❌ {{ $t('app.clear') }}</a>
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
    description: {
      type: String,
      default: null,
    },
    hash: {
      type: String,
      default: null,
    },
  },

  data() {
    return {
      localDescription: null,
      editMode: false,
      form: {
        description: null,
        errors: [],
      },
      loadingState: '',
    };
  },

  created: function() {
    this.localDescription = this.description;
    this.form.description = this.description;
  },

  methods: {
    submit() {
      this.loadingState = 'loading';

      axios.post('/people/' + this.hash + '/description', this.form)
        .then(response => {
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
