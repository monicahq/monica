<style scoped>
</style>

<template>
  <div class="br2 pa3 mb3 f6" :class="[editMode ? 'bg-washed-yellow b--yellow ba' : 'bg-near-white']">

    <notifications group="main" position="bottom right" />

    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">{{ $t('people.interests_title') }}</h3>
      </div>
      <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]" v-if="interests.length > 0">
        <a class="pointer" @click="editMode = true" v-if="!editMode">{{ $t('app.edit') }}</a>
        <a class="pointer" @click="[editMode = false, addMode = false]" v-else>{{ $t('app.done') }}</a>
      </div>
    </div>

    <!-- Add button when box is empty -->
    <p class="mb0" v-if="interests.length == 0 && !addMode">
      <a class="pointer" @click="toggleAdd">{{ $t('app.add') }}</a>
    </p>

    <!-- List of interests -->
    <ul v-if="interests.length > 0">
      <li v-for="interest in interests" class="mb2" :key="interest.id">

        <div class="w-100 dt" v-show="!interest.edit">
          <div class="dtc">
            <span v-if="interest.name">{{ interest.name }}</span>
          </div>
          <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]" v-if="editMode">
            <i class="fa fa-pencil-square-o pointer pr2" @click="toggleEdit(interest)"></i>
            <i class="fa fa-trash-o pointer" @click="trash(interest)"></i>
          </div>
        </div>

        <!-- Interest edit form -->
        <div class="w-100" v-show="interest.edit">
          <form class="measure center">
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.interests_name') }}
              </label>
              <input class="pa2 db w-100" @keyup.enter="update(interest)" type="text" v-model="updateForm.name">
            </div>
            <div class="lh-copy mt3">
              <a @click.prevent="update(interest)" class="btn btn-primary">{{ $t('app.save') }}</a>
              <a class="btn" @click="toggleEdit(interest)">{{ $t('app.cancel') }}</a>
            </div>
          </form>
        </div>

      </li>
      <li v-if="editMode && !addMode">
        <a class="pointer" @click="toggleAdd">{{ $t('app.add') }}</a>
      </li>
    </ul>

    <!-- Interest Add form -->
    <div v-if="addMode">
      <form class="measure center">
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.interests_name') }}
          </label>
          <input class="pa2 db w-100" type="text" @keyup.enter="store" @keyup.esc="addMode = false" v-model="createForm.name">
        </div>
        <div class="lh-copy mt3">
          <a @click.prevent="store" class="btn btn-primary">{{ $t('app.add') }}</a>
          <a class="btn" @click="addMode = false">{{ $t('app.cancel') }}</a>
        </div>
      </form>
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
                interestCategories: [],
                interests: [],

                editMode: false,
                addMode: false,
                updateMode: false,

                createForm: {
                    name: '',
                    errors: []
                },

                updateForm: {
                    id: '',
                    name: '',
                    edit: false,
                    errors: []
                },

                dirltr: true,
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

        props: ['hash'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.dirltr = this.$root.htmldir == 'ltr';
                this.getInterestCategories();
                this.getInterests();
            },

            getInterestCategories() {
                axios.get('/interestcategories')
                        .then(response => {
                            this.interestCategories = response.data;
                        });
            },

            getInterests() {
                axios.get('/people/' + this.hash + '/interests')
                        .then(response => {
                            this.interests = response.data;
                        });
            },

            store() {
                axios.post('/people/' + this.hash + '/interests', this.createForm)
                      .then(response => {
                          this.addMode = false;
                          this.interests.push(response.data);
                          this.createForm.name = '';

                          this.$notify({
                              group: 'main',
                              title: this.$t('people.interests_create_success'),
                              text: '',
                              type: 'success'
                          });
                      });
            },

            toggleAdd() {
                this.addMode = true;
                this.createForm.data = '';
            },

            toggleEdit(interest) {
                Vue.set(interest, 'edit', !interest.edit);
                this.updateForm.id = interest.id;
                this.updateForm.name = interest.name;
            },

            update(interest) {
                axios.put('/people/' + this.hash + '/interests/' + interest.id, this.updateForm)
                      .then(response => {
                          Vue.set(interest, 'edit', !interest.edit);
                          Vue.set(interest, 'name', response.data.name);

                          this.$notify({
                              group: 'main',
                              title: this.$t('people.interests_update_success'),
                              text: '',
                              type: 'success'
                          });
                      });
            },

            trash(interest) {
                axios.delete('/people/' + this.hash + '/interests/' + interest.id)
                      .then(response => {
                          this.getInterests();

                          this.$notify({
                              group: 'main',
                              title: this.$t('people.interests_delete_success'),
                              text: '',
                              type: 'success'
                          });
                      });

                if (this.interests.length <= 1) {
                    this.editMode = false;
                }
            },
        }
    }
</script>
