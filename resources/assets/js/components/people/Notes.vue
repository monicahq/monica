<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <div>
      <img src="/img/people/notes.svg" class="icon-section icon-notes">
      <h3>
        {{ trans('people.section_personal_notes') }}
      </h3>
    </div>

    <div>

      <!-- EMPTY STATE -->
      <div v-if="notes.length == 0 && !addMode" class="tc bg-near-white b--moon-gray pa3">
        <p>{{ trans('people.notes_blank_title') }}</p>
        <p><a class="pointer">{{ trans('people.notes_add_note') }}</a></p>
      </div>

      <div>
        <form class="bg-near-white pa2 br2 mt3 mb3">
          <textarea class="w-100" v-model="createForm.body"></textarea>
          <a class="pointer" @click.prevent="store">Add</a>
        </form>
      </div>

      <!-- LIST OF NORMAL NOTES -->
      <ul>
        <li v-for="note in notes">
          <div class="ba br2 b--black-10 br--top w-100 mb2">
            <div class="pa2">
              <vue-markdown>{{ note.body }}</vue-markdown>
            </div>
            <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
              <div class="fl w-50">
                <i class="fa fa-star-o" @click="toggleFavorite(note)"></i>
                {{ note.created_at }}
              </div>
              <div class="fl w-50 tr">
                <a class="pointer">{{ trans('app.edit') }}</a>
                |
                <a class="pointer">{{ trans('app.delete') }}</a>
              </div>
            </div>
          </div>
        </li>
      </ul>

    </div>

  </div>
</template>

<script>
    import VueMarkdown from 'vue-markdown';

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                notes: [],

                updateMode: false,
                addMode: false,
                editMode: false,

                createForm: {
                    body: '',
                    is_favorited: 0
                }
            };
        },

        components: {
            VueMarkdown
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
                this.getNotes();
            },

            reinitialize() {
                this.createForm.body = '';
            },

            favorited: function (notes) {
              return notes.filter(function (note) {
                return note.is_favorited === true
              })
            },

            normal: function (notes) {
              return notes.filter(function (note) {
                return note.is_favorited === false
              })
            },

            getNotes() {
                axios.get('/people/' + this.contactId + '/notes')
                        .then(response => {
                            this.notes = response.data;
                        });
            },

            store() {
                this.persistClient(
                    'post', '/people/' + this.contactId + '/notes',
                    this.createForm, 'people.notes_create_success'
                );
            },

            toggleFavorite(note) {
                axios.post('/people/' + this.contactId + '/notes/' + note.id + '/toggle')
                      .then(response => {
                          this.getNotes();
                      });
            },

            persistClient(method, uri, form, message) {
                form.errors = {};

                axios[method](uri, form)
                    .then(response => {
                        this.reinitialize();
                        this.getNotes();

                        this.$notify({
                            group: 'main',
                            title: _.get(window.trans, message),
                            text: '',
                            type: 'error'
                        });
                    })
                    .catch(error => {
                        if (typeof error.response.data === 'object') {
                            form.errors = _.flatten(_.toArray(error.response.data));
                        } else {
                            form.errors = ['Something went wrong. Please try again.'];
                        }

                        this.$notify({
                            group: 'main',
                            title: _.get(window.trans, 'app.error_title'),
                            text: '',
                            type: 'error'
                        });
                    });
            },
        }
    }
</script>
