<style scoped>
</style>

<template>
  <div>
    <notifications group="activityTypes" position="bottom right" />

    <h3 class="with-actions">
      {{ $t('settings.personalization_activity_type_category_title') }}
      <a class="btn nt2" v-if="!limited" cy-name="add-activity-category-type-button" :class="[ dirltr ? 'fr' : 'fl' ]" @click="showCreateCategoryModal">{{ $t('settings.personalization_activity_type_category_add') }}</a>
    </h3>
    <p>{{ $t('settings.personalization_activity_type_category_description') }}</p>

    <div class="mt3 mb3 form-information-message br2" v-if="limited">
      <div class="pa3 flex">
        <div class="mr3">
          <svg viewBox="0 0 20 20"><g fill-rule="evenodd"><circle cx="10" cy="10" r="9" fill="currentColor"></circle><path d="M10 0C4.486 0 0 4.486 0 10s4.486 10 10 10 10-4.486 10-10S15.514 0 10 0m0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8m1-5v-3a1 1 0 0 0-1-1H9a1 1 0 1 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2m-1-5.9a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2"></path></g></svg>
        </div>
        <div cy-name="activity-type-premium-message">
          {{ $t('settings.personalisation_paid_upgrade') }}
        </div>
      </div>
    </div>

    <div class="dt dt--fixed w-100 collapse br--top br--bottom">
      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_activity_type_category_table_name') }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_activity_type_category_table_actions') }}
          </div>
        </div>
      </div>
    </div>

    <div cy-name="activity-types">
      <div class="dt dt--fixed w-100 collapse br--top br--bottom" v-for="activityTypeCategory in activityTypeCategories" :key="activityTypeCategory.id">
        <div class="dt-row hover bb b--light-gray">
          <div class="dtc">
            <div class="pa2 b">
              <strong>{{ activityTypeCategory.name }}</strong>
            </div>
          </div>
          <div class="dtc">
            <div class="pa2" :class="[ dirltr ? 'tr' : 'tl' ]" >
              <i class="fa fa-pencil-square-o pointer pr2" :cy-name="'activity-type-category-edit-button-' + activityTypeCategory.id" v-if="!limited" @click="showEditCategory(activityTypeCategory)"></i>
              <i class="fa fa-trash-o pointer" :cy-name="'activity-type-category-delete-button-' + activityTypeCategory.id" v-if="!limited" @click="showDeleteCategory(activityTypeCategory)"></i>
            </div>
          </div>
        </div>
        <div class="dt-row hover bb b--light-gray" :cy-name="'activity-type-id-' + activityType.id" v-for="activityType in activityTypeCategory.activityTypes" :key="activityType.id">
          <div class="dtc">
            <div class="pa2 pl4">
              {{ activityType.name }}
            </div>
          </div>
          <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]" >
            <div class="pa2">
              <i class="fa fa-pencil-square-o pointer pr2" :cy-name="'activity-type-edit-button-' + activityType.id" v-if="!limited" @click="showEditType(activityType)"></i>
              <i class="fa fa-trash-o pointer" :cy-name="'activity-type-delete-button-' + activityType.id" v-if="!limited" @click="showDeleteType(activityType)"></i>
            </div>
          </div>
        </div>
        <div class="dt-row" v-if="!limited">
          <div class="dtc">
            <div class="pa2 pl4">
              <a class="pointer" :cy-name="'add-activity-type-button-for-category-' + activityTypeCategory.id" @click="showCreateTypeModal(activityTypeCategory)">{{ $t('settings.personalization_activity_type_add_button') }}</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Activity Type Category -->
    <sweet-modal ref="createCategoryModal" overlay-theme="dark" :title="$t('settings.personalization_activity_type_category_modal_add')">
      <form @submit.prevent="storeCategory()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            v-model="createCategoryForm.name"
            :input-type="'text'"
            :id="'add-category-name'"
            :required="true"
            :title="$t('settings.personalization_activity_type_category_modal_question')">
          </form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeCategoryModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="storeCategory()" cy-name="add-activity-type-category-button" class="btn btn-primary">{{ $t('app.save') }}</a>
        </span>
      </div>
    </sweet-modal>

    <!-- Update Activity Type Category -->
    <sweet-modal ref="updateCategoryModal" overlay-theme="dark" :title="$t('settings.personalization_activity_type_category_modal_edit')">
      <form @submit.prevent="updateCategory()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            v-model="updateCategoryForm.name"
            :input-type="'text'"
            :id="'update-category-name'"
            :required="true"
            :title="$t('settings.personalization_activity_type_category_modal_question')">
          </form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeUpdateCategoryModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="updateCategory()" cy-name="update-activity-type-category-button" class="btn btn-primary">{{ $t('app.update') }}</a>
        </span>
      </div>
    </sweet-modal>

    <!-- Create Activity Type -->
    <sweet-modal ref="createTypeModal" overlay-theme="dark" :title="$t('settings.personalization_activity_type_modal_add')">
      <form @submit.prevent="storeType()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            v-model="createTypeForm.name"
            :input-type="'text'"
            :id="'add-type-name'"
            :required="true"
            :title="$t('settings.personalization_activity_type_modal_question')">
          </form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeCreateTypeModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="storeType()" :cy-name="'add-type-button'" class="btn btn-primary">{{ $t('app.save') }}</a>
        </span>
      </div>
    </sweet-modal>

    <!-- Update Activity Type -->
    <sweet-modal ref="updateTypeModal" overlay-theme="dark" :title="$t('settings.personalization_activity_type_modal_edit')">
      <form @submit.prevent="updateType()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            v-model="updateTypeForm.name"
            :input-type="'text'"
            :id="'update-type-name'"
            :required="true"
            :title="$t('settings.personalization_activity_type_modal_question')">
          </form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeUpdateTypeModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="updateType()" :cy-name="'update-type-button'" class="btn btn-primary">{{ $t('app.update') }}</a>
        </span>
      </div>
    </sweet-modal>

    <!-- Delete Activiy type category -->
    <sweet-modal ref="deleteCategoryModal" overlay-theme="dark" :title="$t('settings.personalization_activity_type_category_modal_delete')">
      <form>
        <div class="form-error-message mb3" v-if="errorMessage != ''">
          <div class="pa2">
            <p class="mb0">{{ errorMessage }}</p>
          </div>
        </div>
        <div class="mb4">
          <p class="mb2">{{ $t('settings.personalization_activity_type_category_modal_delete_desc') }}</p>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeDeleteCategoryModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="destroyCategory()" :cy-name="'delete-category-button'" class="btn btn-primary">{{ $t('app.delete') }}</a>
        </span>
      </div>
    </sweet-modal>

    <!-- Delete Activiy type  -->
    <sweet-modal ref="deleteTypeModal" overlay-theme="dark" :title="$t('settings.personalization_activity_type_modal_delete')">
      <form>
        <div class="form-error-message mb3" v-if="errorMessage != ''">
          <div class="pa2">
            <p class="mb0">{{ errorMessage }}</p>
          </div>
        </div>
        <div class="mb4">
          <p class="mb2">{{ $t('settings.personalization_activity_type_modal_delete_desc') }}</p>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeDeleteTypeModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="destroyType()" :cy-name="'delete-type-button'" class="btn btn-primary">{{ $t('app.delete') }}</a>
        </span>
      </div>
    </sweet-modal>

  </div>
</template>

<script>
    import { SweetModal, SweetModalTab } from 'sweet-modal-vue';

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                activityTypes: [],
                activityTypeCategories: [],
                errorMessage: '',

                updatedCategory: {
                    id: '',
                    name: ''
                },

                createCategoryForm: {
                    name: '',
                    errors: []
                },

                createTypeForm: {
                    name: '',
                    activity_type_category_id: '',
                    errors: []
                },

                updateCategoryForm: {
                    id: '',
                    name: '',
                    errors: []
                },

                updateTypeForm: {
                    id: '',
                    name: '',
                    errors: []
                },

                destroyCategoryForm: {
                    id: '',
                    errors: []
                },

                destroyTypeForm: {
                    id: '',
                    errors: []
                },

                dirltr: true,
            };
        },

        components: {
            SweetModal,
            SweetModalTab
        },

        props: ['limited'],

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.dirltr = this.$root.htmldir == 'ltr';
                this.getActivityTypeCategories();
            },

            getActivityTypeCategories() {
                axios.get('/settings/personalization/activitytypecategories')
                        .then(response => {
                            this.activityTypeCategories = response.data;
                        });
            },

            closeCategoryModal() {
                this.$refs.createCategoryModal.close();
            },

            closeDeleteCategoryModal() {
                this.$refs.deleteCategoryModal.close();
            },

            showCreateCategoryModal() {
                this.$refs.createCategoryModal.open();
            },

            storeCategory() {
                axios.post('/settings/personalization/activitytypecategories', this.createCategoryForm)
                      .then(response => {
                          this.$refs.createCategoryModal.close();
                          this.activityTypeCategories.push(response.data);
                          this.createCategoryForm.name = '';

                          this.notify(this.$t('app.default_save_success'), true);
                      });
            },

            showEditCategory(category) {
                this.updateCategoryForm.id = category.id;
                this.updateCategoryForm.name = category.name;
                this.updatedCategory = category;

                this.$refs.updateCategoryModal.open();
            },

            showDeleteCategory(category) {
                this.destroyCategoryForm.id = category.id;

                this.$refs.deleteCategoryModal.open();
            },

            showDeleteType(type) {
                this.destroyTypeForm.id = type.id;

                this.$refs.deleteTypeModal.open();
            },

            showEditType(type) {
                this.updateTypeForm.id = type.id;
                this.updateTypeForm.name = type.name;

                this.$refs.updateTypeModal.open();
            },

            closeUpdateCategoryModal() {
                this.$refs.updateCategoryModal.close();
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

            updateCategory() {
                axios.put('/settings/personalization/activitytypecategories/' + this.updateCategoryForm.id, this.updateCategoryForm)
                      .then(response => {
                          this.$refs.updateCategoryModal.close();
                          this.updatedCategory.name = this.updateCategoryForm.name;
                          this.updateCategoryForm.name = '';

                          this.notify(this.$t('app.default_save_success'), true);
                      });
            },

            showCreateTypeModal(category) {
                this.$refs.createTypeModal.open();
                this.createTypeForm.activity_type_category_id = category.id;
            },

            storeType() {
                axios.post('/settings/personalization/activitytypes', this.createTypeForm)
                      .then(response => {
                          this.$refs.createTypeModal.close();
                          this.activityTypes.push(response.data);
                          this.createTypeForm.name = '';
                          this.getActivityTypeCategories();

                          this.notify(this.$t('app.default_save_success'), true);
                      });
            },

            destroyCategory() {
                axios.delete('/settings/personalization/activitytypecategories/' + this.destroyCategoryForm.id)
                      .then(response => {
                          this.$refs.deleteCategoryModal.close();
                          this.destroyCategoryForm.id = '';
                          this.getActivityTypeCategories();

                          this.notify(this.$t('app.default_save_success'), true);
                      })
                      .catch(error => {
                          this.errorMessage = error.response.data.message;
                      });
            },

            updateType() {
                axios.put('/settings/personalization/activitytypes/' + this.updateTypeForm.id, this.updateTypeForm)
                      .then(response => {
                          this.$refs.updateTypeModal.close();
                          this.updatedCategory.name = this.updateTypeForm.name;
                          this.updateTypeForm.name = '';
                          this.getActivityTypeCategories();

                          this.notify(this.$t('app.default_save_success'), true);
                      });
            },

            destroyType() {
                axios.delete('/settings/personalization/activitytypes/' + this.destroyTypeForm.id)
                      .then(response => {
                          this.$refs.deleteTypeModal.close();
                          this.destroyTypeForm.id = '';
                          this.getActivityTypeCategories();

                          this.notify(this.$t('app.default_save_success'), true);
                      })
                      .catch(error => {
                          this.errorMessage = error.response.data.message;
                      });
            },

            notify(text, success) {
                this.$notify({
                    group: 'activityTypes',
                    title: text,
                    text: '',
                    type: success ? 'success' : 'error'
                });
            }
        }
    }
</script>
