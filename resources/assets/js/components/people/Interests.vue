<style scoped>
</style>

<template>
  <div class="br2 pa3 mb3 f6" :class="[editMode ? 'bg-washed-yellow b--yellow ba' : 'bg-near-white']">
    <notifications group="main" position="bottom right" />

    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">
          {{ $t('people.interests_title') }}
        </h3>
      </div>
      <div v-if="interests.length > 0" class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
        <a v-if="!editMode" class="pointer" @click="editMode = true">
          {{ $t('app.edit') }}
        </a>
        <a v-else class="pointer" @click="[editMode = false, addMode = false]">
          {{ $t('app.done') }}
        </a>
      </div>
    </div>

    <!-- Add button when box is empty -->
    <p v-if="interests.length == 0 && !addMode" class="mb0">
      <a class="pointer" @click="toggleAdd">
        {{ $t('app.add') }}
      </a>
    </p>

    <!-- List of interests -->
    <ul v-if="interests.length > 0">
      <li v-for="interest in interests" :key="interest.id" class="mb2">
        <div v-show="!interest.edit" class="w-100 dt">
          <div class="dtc">
            <span v-if="interest.name">
              {{ interest.name }}
            </span>
          </div>
          <div v-if="editMode" class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
            <i class="fa fa-pencil-square-o pointer pr2" @click="toggleEdit(interest)"></i>
            <i class="fa fa-trash-o pointer" @click="trash(interest)"></i>
          </div>
        </div>

        <!-- Interest edit form -->
        <div v-show="interest.edit" class="w-100">
          <form class="measure center">
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.interests_name') }}
              </label>
              <input v-model="updateForm.name" class="pa2 db w-100" type="text" @keyup.enter="update(interest)" />
            </div>
            <div class="lh-copy mt3">
              <a class="btn btn-primary" @click.prevent="update(interest)">
                {{ $t('app.save') }}
              </a>
              <a class="btn" @click="toggleEdit(interest)">
                {{ $t('app.cancel') }}
              </a>
            </div>
          </form>
        </div>
      </li>
      <li v-if="editMode && !addMode">
        <a class="pointer" @click="toggleAdd">
          {{ $t('app.add') }}
        </a>
      </li>
    </ul>

    <!-- Interest Add form -->
    <div v-if="addMode">
      <form class="measure center">
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            {{ $t('people.interests_name') }}
          </label>
          <input v-model="createForm.name" class="pa2 db w-100" type="text" @keyup.enter="store" @keyup.esc="addMode = false" />
        </div>
        <div class="lh-copy mt3">
          <a class="btn btn-primary" @click.prevent="store">
            {{ $t('app.add') }}
          </a>
          <a class="btn" @click="addMode = false">
            {{ $t('app.cancel') }}
          </a>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {

    props: {
        hash: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
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

    mounted() {
        this.prepareComponent();
    },

    methods: {
        prepareComponent() {
            this.dirltr = this.$root.htmldir == 'ltr';
            this.getInterests();
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
};
</script>
