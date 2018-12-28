<style scoped>
  .note:hover {
      background-color: #f6fbff;
  }
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <div>
      <div>
        <form class="bg-near-white pa2 br2 mb3">
          <textarea v-model="newNote.body" class="w-100 br2 pa2 b--light-gray" cy-name="add-note-textarea" :placeholder="$t('people.notes_add_cta')" @focus="addMode = true"
                    @keyup.esc="addMode = false"
          ></textarea>
          <a v-if="addMode" class="pointer btn btn-primary" cy-name="add-note-button" @click.prevent="store">
            {{ $t('app.add') }}
          </a>
          <a v-if="addMode" class="pointer btn btn-secondary" cy-name="cancel-note-button" @click="addMode = false">
            {{ $t('app.cancel') }}
          </a>
        </form>
      </div>

      <!-- LIST OF NORMAL NOTES -->
      <ul>
        <li v-for="note in notes" :key="note.id" class="note">
          <div v-show="!note.edit" class="ba br2 b--black-10 br--top w-100 mb2" :cy-name="'note-body-' + note.id">
            <div class="pa2 markdown">
              <span v-html="note.parsed_body"></span>
            </div>
            <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
              <div class="fl w-50">
                <div class="f5 di mr1">
                  <i v-tooltip.top="$t('people.notes_favorite')" class="pointer" :class="[note.is_favorited ? 'fa fa-star' : 'fa fa-star-o']" @click="toggleFavorite(note)"></i>
                </div>
                {{ note.created_at_short }}
              </div>
              <div class="fl w-50 tr">
                <a class="pointer" :cy-name="'edit-note-button-' + note.id" @click="toggleEditMode(note)">
                  {{ $t('app.edit') }}
                </a>
                |
                <a class="pointer" :cy-name="'delete-note-button-' + note.id" @click.prevent="showDelete(note)">
                  {{ $t('app.delete') }}
                </a>
              </div>
            </div>
          </div>

          <!-- EDIT MODE -->
          <form v-show="note.edit" class="bg-near-white pa2 br2 mt3 mb3">
            <textarea v-model="note.body" class="w-100 br2 pa2 b--light-gray" :cy-name="'edit-note-body-' + note.id" @keyup.esc="note.edit = false"></textarea>
            <a class="pointer btn btn-primary" :cy-name="'edit-mode-note-button-' + note.id" @click.prevent="update(note)">
              {{ $t('app.update') }}
            </a>
          </form>
        </li>
      </ul>
    </div>

    <!-- Delete Note modal -->
    <div id="modal-delete-note" class="modal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ $t('people.notes_delete_title') }}
            </h5>
            <button type="button" class="close" :class="[dirltr ? '' : 'rtl']" data-dismiss="modal">
              <span aria-hidden="true">
                &times;
              </span>
            </button>
          </div>
          <div class="modal-body">
            <p>{{ $t('people.notes_delete_confirmation') }}</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              {{ $t('app.cancel') }}
            </button>
            <button type="button" class="btn btn-danger" :cy-name="'delete-mode-note-button-' + deleteNote.id" @click.prevent="trash(deleteNote)">
              {{ $t('app.delete') }}
            </button>
          </div>
        </div>
      </div>
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
            notes: [],

            addMode: false,
            editMode: false,

            newNote: {
                id: 0,
                body: '',
                is_favorited: 0
            },

            deleteNote: {
                id: 0,
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
            this.getNotes();
        },

        reinitialize() {
            this.createForm.body = '';
        },

        favorited: function (notes) {
            return notes.filter(function (note) {
                return note.is_favorited === true;
            });
        },

        normal: function (notes) {
            return notes.filter(function (note) {
                return note.is_favorited === false;
            });
        },

        toggleEditMode(note) {
            Vue.set(note, 'edit', !note.edit);
        },

        getNotes() {
            axios.get('/people/' + this.hash + '/notes')
                .then(response => {
                    this.notes = response.data;
                });
        },

        store() {
            axios.post('/people/' + this.hash + '/notes', this.newNote)
                .then(response => {
                    this.newNote.body = '';
                    this.getNotes();
                    this.addMode = false;

                    this.$notify({
                        group: 'main',
                        title: this.$t('people.notes_create_success'),
                        text: '',
                        type: 'success'
                    });
                });
        },

        toggleFavorite(note) {
            axios.post('/people/' + this.hash + '/notes/' + note.id + '/toggle')
                .then(response => {
                    this.getNotes();
                });
        },

        update(note) {
            axios.put('/people/' + this.hash + '/notes/' + note.id, note)
                .then(response => {
                    Vue.set(note, 'edit', note.edit);
                    this.getNotes();

                    this.$notify({
                        group: 'main',
                        title: this.$t('people.notes_update_success'),
                        text: '',
                        type: 'success'
                    });
                });
        },

        showDelete(note) {
            this.deleteNote.id = note.id;

            $('#modal-delete-note').modal('show');
        },

        trash(note) {
            axios.delete('/people/' + this.hash + '/notes/' + note.id)
                .then(response => {
                    this.getNotes();

                    $('#modal-delete-note').modal('hide');

                    this.$notify({
                        group: 'main',
                        title: this.$t('people.notes_delete_success'),
                        text: '',
                        type: 'success'
                    });
                });
        },
    }
};
</script>
