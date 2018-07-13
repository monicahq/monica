<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <h3 class="with-actions">
      {{ $t('settings.personalization_contact_field_type_title') }}
      <a class="btn nt2" v-bind:class="[ dirltr ? 'fr' : 'fl' ]" @click="add">{{ $t('settings.personalization_contact_field_type_add') }}</a>
    </h3>
    <p>{{ $t('settings.personalization_contact_field_type_description') }}</p>

    <div class="pa2 ba b--yellow mb3 mt3 br2 bg-washed-yellow" v-if="submitted">
      {{ $t('settings.personalization_contact_field_type_add_success') }}
    </div>

    <div class="pa2 ba b--yellow mb3 mt3 br2 bg-washed-yellow" v-if="edited">
      {{ $t('settings.personalization_contact_field_type_edit_success') }}
    </div>

    <div class="pa2 ba b--yellow mb3 mt3 br2 bg-washed-yellow" v-if="deleted">
      {{ $t('settings.personalization_contact_field_type_delete_success') }}
    </div>

    <div class="dt dt--fixed w-100 collapse br--top br--bottom">

      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_name') }}
          </div>
        </div>
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_protocol') }}
          </div>
        </div>
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div class="bb b--light-gray" v-for="activityTypeCategory in activityTypeCategories" v-bind:key="activityTypeCategory.id">
        <div class="dt-row ">
          <div class="dtc">
            <div class="pa2">
              {{ activityTypeCategory.name }}
            </div>
          </div>
          <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]" >
            <div class="pa2">
              <i class="fa fa-pencil-square-o pointer pr2" @click="edit(activityTypeCategory)"></i>
              <i class="fa fa-trash-o pointer" @click="showDelete(activityTypeCategory)"></i>
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

    </div>

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

                submitted: false,
                edited: false,
                deleted: false,

                createForm: {
                    name: '',
                    protocol: '',
                    icon: '',
                    errors: []
                },

                editForm: {
                    id: '',
                    name: '',
                    protocol: '',
                    icon: '',
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
            }
        }
    }
</script>
