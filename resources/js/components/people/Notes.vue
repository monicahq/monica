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
          <textarea v-model="newNote.body" v-cy-name="'add-note-textarea'" class="w-100 br2 pa2 b--light-gray" :placeholder="$t('people.notes_add_cta')" @focus="addMode = true"
                    @keyup.esc="addMode = false"
          ></textarea>
          <a v-if="addMode" v-cy-name="'add-note-button'" class="pointer btn btn-primary" href="" @click.prevent="store">
            {{ $t('app.add') }}
          </a>
          <a v-if="addMode" v-cy-name="'cancel-note-button'" class="pointer btn btn-secondary" href="" @click.prevent="addMode = false">
            {{ $t('app.cancel') }}
          </a>
        </form>
      </div>

      <!-- LIST OF NORMAL NOTES -->
      <ul v-cy-name="'notes-body'" v-cy-items="notes.map(n => n.id)">
        <li v-for="note in notes" :key="note.id" class="note">
          <div v-show="!note.edit" v-cy-name="'note-body-' + note.id" class="ba br2 b--black-10 br--top w-100 mb2">
            <div class="pa2 markdown">
              <span dir="auto" v-html="note.parsed_body"></span>
            </div>
            <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
              <div class="fl w-50">
                <div class="f5 di mr1">
                  <em v-tooltip.top="$t('people.notes_favorite')" class="pointer" :class="[note.is_favorited ? 'fa fa-star' : 'fa fa-star-o']" @click="toggleFavorite(note)"></em>
                </div>
                {{ note.created_at_short }}
              </div>
              <div class="fl w-50 tr">
                <a v-cy-name="'edit-note-button-' + note.id" class="pointer" href="" @click.prevent="toggleEditMode(note)">
                  {{ $t('app.edit') }}
                </a>
                |
                <a v-cy-name="'delete-note-button-' + note.id" class="pointer" href="" @click.prevent="showDelete(note)">
                  {{ $t('app.delete') }}
                </a>
              </div>
            </div>
          </div>

          <!-- EDIT MODE -->
          <form v-show="note.edit" class="bg-near-white pa2 br2 mt3 mb3">
            <textarea v-model="note.body" v-cy-name="'edit-note-body-' + note.id" class="w-100 br2 pa2 b--light-gray" @keyup.esc="note.edit = false"></textarea>
            <a v-cy-name="'edit-mode-note-button-' + note.id" class="pointer btn btn-primary" href="" @click.prevent="update(note)">
              {{ $t('app.update') }}
            </a>
          </form>
        </li>
      </ul>
    </div>

    <!-- Delete Note modal -->
    <sweet-modal ref="modalDeleteNote" v-cy-name="'modal-delete-note'"
                 overlay-theme="dark"
                 :title="$t('people.notes_delete_title')"
    >
      <p>
        {{ $t('people.notes_delete_confirmation') }}
      </p>
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeModal">
          {{ $t('app.cancel') }}
        </a>
        <a v-cy-name="'delete-mode-note-button-' + deleteNote.id" class="btn btn-primary" href=""
           @click.prevent="trash(deleteNote)"
        >
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
    SweetModal,
  },

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
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
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
      axios.get('people/' + this.hash + '/notes')
        .then(response => {
          this.notes = response.data;
        });
    },

    store() {
      axios.post('people/' + this.hash + '/notes', this.newNote)
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
      axios.post('people/' + this.hash + '/notes/' + note.id + '/toggle')
        .then(response => {
          this.getNotes();
        });
    },

    update(note) {
      axios.put('people/' + this.hash + '/notes/' + note.id, note)
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
      this.$refs.modalDeleteNote.open();
    },

    closeModal() {
      this.$refs.modalDeleteNote.close();
    },

    trash(note) {
      axios.delete('people/' + this.hash + '/notes/' + note.id)
        .then(response => {
          this.getNotes();

          this.closeModal();

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
