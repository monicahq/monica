<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <a @click="showUpdate">Stay in touch</a>

    <!-- Create Gender type -->
    <sweet-modal ref="updateModal" overlay-theme="dark" :title="$t('settings.personalization_genders_modal_add')">
      <form v-on:submit.prevent="store()">
        <div class="mb4">
          <p class="b mb2"></p>
          <form-input
            v-model="createForm.name"
            v-bind:input-type="'text'"
            v-bind:id="''"
            v-bind:required="true"
            v-bind:title="$t('settings.personalization_genders_modal_question')">
          </form-input>
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="store()" class="btn btn-primary">{{ $t('app.save') }}</a>
        </span>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
    import { SweetModal } from 'sweet-modal-vue';

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                frequency: null,
                dirltr: true,
            };
        },

        components: {
            SweetModal
        },

        props: ['contact'],

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
            },

            getStayInTouchInfo() {
                axios.get('/settings/personalization/genders')
                        .then(response => {
                            this.genders = response.data;
                        });
            },

            showUpdate() {
                this.$refs.updateModal.open();
            },

            update() {
                axios.put('/settings/personalization/genders/' + this.updateForm.id, this.updateForm)
                      .then(response => {
                          this.$refs.updateModal.close();
                          this.updatedGender.name = this.updateForm.name;
                          this.updateForm.name = '';
                      });
            },
        }
    }
</script>
