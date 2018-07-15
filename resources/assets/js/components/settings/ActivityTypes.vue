<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="with-actions">
      {{ $t('settings.personalization_activity_type_category_title') }}
      <a class="btn nt2" v-bind:class="[ dirltr ? 'fr' : 'fl' ]" @click="showCreateCategoryModal">{{ $t('settings.personalization_activity_type_category_add') }}</a>
    </h3>
    <p>{{ $t('settings.personalization_activity_type_category_description') }}</p>

    <div class="dt dt--fixed w-100 collapse br--top br--bottom">

      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_activity_type_category_table_name') }}
          </div>
        </div>
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_activity_type_category_table_actions') }}
          </div>
        </div>
      </div>

      <div class="dt-row bb b--light-gray" v-for="activityTypeCategory in activityTypeCategories" v-bind:key="activityTypeCategory.id">
        <div class="dtc">
          <div class="pa2">
            {{ activityTypeCategory.name }}
          </div>
        </div>
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]" >
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2" @click="showEditCategory(activityTypeCategory)"></i>
            <i class="fa fa-trash-o pointer" @click="showDeleteCategory(activityTypeCategory)"></i>
          </div>
        </div>
      </div>
      <div class="dt-row">
        <div class="dtc">
          <div class="pa2">
            <a href="">Add a new activity type</a>
          </div>
        </div>
      </div>
    </div>


    <div class="dt dt--fixed w-100 collapse br--top br--bottom">
      <div class="dt-row bb b--light-gray">
        <div class="dtc">
          <div class="pa2 b">
            Test
          </div>
        </div>
        <div class="dtc">
          <div class="pa2" v-bind:class="[ dirltr ? 'tr' : 'tl' ]" >
            <i class="fa fa-pencil-square-o pointer pr2"></i>
            <i class="fa fa-trash-o pointer"></i>
          </div>
        </div>
      </div>
      <div class="dt-row bb b--light-gray">
        <div class="dtc">
          <div class="pa2 pl4">
            Test
          </div>
        </div>
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]" >
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2"></i>
            <i class="fa fa-trash-o pointer"></i>
          </div>
        </div>
      </div>
      <div class="dt-row bb b--light-gray">
        <div class="dtc">
          <div class="pa2 pl4">
            Test
          </div>
        </div>
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]" >
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2"></i>
            <i class="fa fa-trash-o pointer"></i>
          </div>
        </div>
      </div>
      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 pl4">
            <a href="">Add a new activity type</a>
          </div>
        </div>
      </div>
    </div>
    <div class="dt dt--fixed w-100 collapse br--top br--bottom">
      <div class="dt-row bb b--light-gray">
        <div class="dtc">
          <div class="pa2 b">
            Test
          </div>
        </div>
        <div class="dtc">
          <div class="pa2" v-bind:class="[ dirltr ? 'tr' : 'tl' ]" >
            <i class="fa fa-pencil-square-o pointer pr2"></i>
            <i class="fa fa-trash-o pointer"></i>
          </div>
        </div>
      </div>
      <div class="dt-row bb b--light-gray">
        <div class="dtc">
          <div class="pa2 pl4">
            Test
          </div>
        </div>
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]" >
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2"></i>
            <i class="fa fa-trash-o pointer"></i>
          </div>
        </div>
      </div>
      <div class="dt-row bb b--light-gray">
        <div class="dtc">
          <div class="pa2 pl4">
            Test
          </div>
        </div>
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]" >
          <div class="pa2">
            <i class="fa fa-pencil-square-o pointer pr2"></i>
            <i class="fa fa-trash-o pointer"></i>
          </div>
        </div>
      </div>
      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 pl4">
            <a href="">Add a new activity type</a>
          </div>
        </div>
      </div>
    </div>


    <!-- Create Activity Type Category -->
    <sweet-modal ref="createCategoryModal" overlay-theme="dark" :title="$t('settings.personalization_activity_type_category_modal_add')">
      <form v-on:submit.prevent="storeCategory()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            v-model="createCategoryForm.name"
            v-bind:input-type="'text'"
            v-bind:id="''"
            v-bind:required="true"
            v-bind:title="$t('settings.personalization_activity_type_category_modal_question')">
          </form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeCategoryModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="storeCategory()" class="btn btn-primary">{{ $t('app.save') }}</a>
        </span>
      </div>
    </sweet-modal>

    <!-- Update Activity Type Category -->
    <sweet-modal ref="updateCategoryModal" overlay-theme="dark" :title="$t('settings.personalization_activity_type_category_modal_edit')">
      <form v-on:submit.prevent="updateCategory()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            v-model="updateCategoryForm.name"
            v-bind:input-type="'text'"
            v-bind:id="''"
            v-bind:required="true"
            v-bind:title="$t('settings.personalization_activity_type_category_modal_question')">
          </form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeUpdateCategoryModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="updateCategory()" class="btn btn-primary">{{ $t('app.update') }}</a>
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

                updatedCategory: {
                    id: '',
                    name: ''
                },

                createCategoryForm: {
                    name: '',
                    errors: []
                },

                updateCategoryForm: {
                    id: '',
                    name: '',
                    errors: []
                },

                dirltr: true,
            };
        },

        components: {
            SweetModal,
            SweetModalTab
        },

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
                this.dirltr = $('html').attr('dir') == 'ltr';
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

            showCreateCategoryModal() {
                this.$refs.createCategoryModal.open();
            },

            storeCategory() {
                axios.post('/settings/personalization/activitytypecategories', this.createCategoryForm)
                      .then(response => {
                          this.$refs.createCategoryModal.close();
                          this.activityTypeCategories.push(response.data);
                          this.createCategoryForm.name = '';
                      });
            },

            showEditCategory(category) {
                this.updateCategoryForm.id = category.id;
                this.updateCategoryForm.name = category.name;
                this.updatedCategory = category;

                this.$refs.updateCategoryModal.open();
            },

            closeUpdateCategoryModal() {
                this.$refs.updateCategoryModal.close();
            },

            updateCategory() {
                axios.put('/settings/personalization/activitytypecategories/', this.updateCategoryForm)
                      .then(response => {
                          this.$refs.updateCategoryModal.close();
                          this.updatedCategory.name = this.updateCategoryForm.name;
                          this.updateCategoryForm.name = '';
                      });
            }
        }
    }
</script>
