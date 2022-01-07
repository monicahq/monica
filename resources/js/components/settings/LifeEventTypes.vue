<template>
  <div>
    <notifications group="lifeEventTypes" position="bottom right" />

    <h3 class="with-actions">
      {{ $t('settings.personalization_life_event_category_title') }}
    </h3>
    <p>{{ $t('settings.personalization_life_event_category_description') }}</p>

    <div v-if="limited" class="mt3 mb3 form-information-message br2">
      <div class="pa3 flex">
        <div class="mr3">
          <svg viewBox="0 0 20 20">
            <g fill-rule="evenodd">
              <circle cx="10" cy="10" r="9" fill="currentColor" /><path d="M10 0C4.486 0 0 4.486 0 10s4.486 10 10 10 10-4.486 10-10S15.514 0 10 0m0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8m1-5v-3a1 1 0 0 0-1-1H9a1 1 0 1 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2m-1-5.9a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2" />
            </g>
          </svg>
        </div>
        <div v-html="$t('settings.personalisation_paid_upgrade_vue', {url: 'settings/subscriptions' })"></div>
      </div>
    </div>

    <div class="dt dt--fixed w-100 collapse br--top br--bottom">
      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_live_event_category_table_name') }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_live_event_category_table_actions') }}
          </div>
        </div>
      </div>
    </div>

    <div>
      <ul>
        <li v-for="lifeEventCategory in lifeEventCategories" :key="lifeEventCategory.id" class="dt dt--fixed w-100 collapse br--top br--bottom mt3">
          <!-- LIFE EVENT CATEGORY -->
          <div class="dt-row hover bb b--light-gray">
            <div class="dtc">
              <div class="pa2 b">
                <strong>{{ $t('people.life_event_category_' + lifeEventCategory.default_life_event_category_key) }}</strong>
              </div>
            </div>
            <div class="dtc">
            </div>
          </div>
          <div v-for="lifeEventType in lifeEventCategory.lifeEventTypes" :key="lifeEventType.id" class="dt-row hover bb b--light-gray">
            <div class="dtc">
              <div class="pa2 pl4">
                <template v-if="lifeEventType.name">
                  {{ lifeEventType.name }}
                </template>
                <template v-else>
                  {{ $t('people.life_event_sentence_' + lifeEventType.default_life_event_type_key) }}
                </template>
              </div>
            </div>
            <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
              <div class="pa2">
                <em v-if="!limited" class="fa fa-pencil-square-o pointer pr2" @click="showEditType(lifeEventType, lifeEventCategory.id)"></em>
                <em v-if="!limited" class="fa fa-trash-o pointer" @click="showDeleteType(lifeEventType)"></em>
              </div>
            </div>
          </div>
          <div v-if="!limited" class="dt-row">
            <div class="dtc">
              <div class="pa2 pl4">
                <a class="pointer" href=""
                   @click.prevent="showCreateType(lifeEventCategory)"
                >
                  {{ $t('settings.personalization_life_event_type_add_button') }}
                </a>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Create Life Event Type -->
    <sweet-modal ref="createTypeModal" overlay-theme="dark" :title="$t('settings.personalization_life_event_type_modal_add')">
      <form @submit.prevent="storeType()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            :id="'add-type-name'"
            v-model="createTypeForm.name"
            :input-type="'text'"
            :required="true"
            :title="$t('settings.personalization_life_event_type_modal_question')"
          />
        </div>
      </form>
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeCreateTypeModal()">
          {{ $t('app.cancel') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="storeType()">
          {{ $t('app.save') }}
        </a>
      </div>
    </sweet-modal>

    <!-- Update Life Event Type -->
    <sweet-modal ref="updateTypeModal" overlay-theme="dark" :title="$t('settings.personalization_life_event_type_modal_edit')">
      <form @submit.prevent="updateType()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            :id="'update-type-name'"
            v-model="updateTypeForm.name"
            :input-type="'text'"
            :required="true"
            :title="$t('settings.personalization_life_event_type_modal_question')"
          />
        </div>
      </form>
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeUpdateTypeModal()">
          {{ $t('app.cancel') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="updateType()">
          {{ $t('app.update') }}
        </a>
      </div>
    </sweet-modal>

    <!-- Delete Life Event type  -->
    <sweet-modal ref="deleteTypeModal" overlay-theme="dark" :title="$t('settings.personalization_life_event_type_modal_delete')">
      <form>
        <div v-if="errorMessage !== ''" class="form-error-message mb3">
          <div class="pa2">
            <p class="mb0">
              {{ errorMessage }}
            </p>
          </div>
        </div>
        <div class="mb4">
          <p class="mb2">
            {{ $t('settings.personalization_life_event_type_modal_delete_desc') }}
          </p>
        </div>
      </form>
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeDeleteTypeModal()">
          {{ $t('app.cancel') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="destroyType()">
          {{ $t('app.delete') }}
        </a>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import { SweetModal } from 'sweet-modal-vue';

export default {

  components: {
    SweetModal
  },

  props: {
    limited: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      lifeEventCategories: [],
      errorMessage: '',

      createTypeForm: {
        name: '',
        life_event_category_id: '',
        errors: []
      },

      updateTypeForm: {
        id: '',
        name: '',
        life_event_category_id: '',
        errors: []
      },

      destroyTypeForm: {
        id: '',
        errors: []
      },
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.getLifeEventCategories();
    },

    getLifeEventCategories() {
      axios.get('settings/personalization/lifeeventcategories')
        .then(response => {
          this.lifeEventCategories = response.data;
        });
    },

    showCreateType(category) {
      this.$refs.createTypeModal.open();
      this.createTypeForm.life_event_category_id = category.id;
    },

    showDeleteType(type) {
      this.destroyTypeForm.id = type.id;

      this.$refs.deleteTypeModal.open();
    },

    showEditType(type, categoryId) {
      this.updateTypeForm.id = type.id;
      this.updateTypeForm.name = type.name ? type.name : this.$t('people.life_event_sentence_' + type.default_life_event_type_key);
      this.updateTypeForm.life_event_category_id = categoryId;

      this.$refs.updateTypeModal.open();
    },

    closeCreateTypeModal() {
      this.$refs.createTypeModal.close();
    },

    closeUpdateTypeModal() {
      this.$refs.updateTypeModal.close();
    },

    closeDeleteTypeModal() {
      this.$refs.deleteTypeModal.close();
    },

    storeType() {
      axios.post('settings/personalization/lifeeventtypes', this.createTypeForm)
        .then(response => {
          this.$refs.createTypeModal.close();
          this.createTypeForm.name = '';
          this.getLifeEventCategories();

          this.notify(this.$t('app.default_save_success'), true);
        });
    },

    updateType() {
      axios.put('settings/personalization/lifeeventtypes/' + this.updateTypeForm.id, this.updateTypeForm)
        .then(response => {
          this.$refs.updateTypeModal.close();
          this.updateTypeForm.name = '';
          this.getLifeEventCategories();

          this.notify(this.$t('app.default_save_success'), true);
        });
    },

    destroyType() {
      axios.delete('settings/personalization/lifeeventtypes/' + this.destroyTypeForm.id)
        .then(response => {
          this.$refs.deleteTypeModal.close();
          this.destroyTypeForm.id = '';
          this.getLifeEventCategories();

          this.notify(this.$t('app.default_save_success'), true);
        })
        .catch(error => {
          this.errorMessage = error.response.data.message;
        });
    },

    notify(text, success) {
      this.$notify({
        group: 'lifeEventTypes',
        title: text,
        text: '',
        type: success ? 'success' : 'error'
      });
    }
  }
};
</script>
