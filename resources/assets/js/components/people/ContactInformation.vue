<style scoped>
</style>

<template>
  <div>
    <div class="br2 pa2 mb3" v-bind:class="[editMode ? 'bg-washed-yellow b--yellow ba' : 'bg-near-white']">
      <div class="w-100 dt">
        <div class="dtc">
          <h3 class="f6 ttu normal">{{ trans('people.contact_info_title') }}</h3>
        </div>
        <div class="dtc tr">
          <a class="pointer" @click="editMode = true" v-if="!editMode">{{ trans('app.edit') }}</a>
          <a class="pointer" @click="editMode = false" v-if="editMode">{{ trans('app.done') }}</a>
        </div>
      </div>
      <ul>
        <li v-for="contactInformation in contactInformationData" class="mb2">

          <div class="w-100 dt">
            <div class="dtc">
              <i :class="contactInformation.fontawesome_icon" class="pr2 f6 light-silver" v-if="contactInformation.fontawesome_icon"></i>
              <i class="pr2 fa fa-address-card-o f6 gray" v-if="!contactInformation.fontawesome_icon"></i>

              <div class="truncate">
                <a :href="contactInformation.protocol + contactInformation.data" v-if="contactInformation.protocol">{{ contactInformation.data }}</a>
                <span v-if="!contactInformation.protocol">{{ contactInformation.data }}</span>
              </div>
            </div>
            <div class="dtc tr" v-if="editMode">
              <i class="fa fa-pencil-square-o pointer pr2" @click="edit(contactFieldType)"></i>
              <i class="fa fa-trash-o pointer" @click="showDelete(contactFieldType)"></i>
            </div>
          </div>
        </li>
        <li v-if="editMode">
          <a class="pointer">{{ trans('app.edit') }}</a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                contactInformationData: [],

                editMode: false
            };
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

        props: ['contactId'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.getContactInformationData();
            },

            getContactInformationData() {
                axios.get('/people/' + this.contactId + '/contact')
                        .then(response => {
                            this.contactInformationData = response.data;
                        });
            }
        }
    }
</script>
