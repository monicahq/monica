<style scoped>
  .note-information {
    color: #8F9295;
  }

  .absolute {
    border: 1px solid rgba(27,31,35,.15);
    box-shadow: 0 3px 12px rgba(27,31,35,.15);
    right: -7px;
    top: 25px;
  }

  .absolute:after,
  .absolute:before {
    content: "";
    display: inline-block;
    position: absolute;
  }

  .absolute:after {
    border: 7px solid transparent;
    border-bottom-color: #fff;
    left: auto;
    right: 10px;
    top: -14px;
  }

  .absolute:before {
    border: 8px solid transparent;
    border-bottom-color: rgba(27,31,35,.15);
    left: auto;
    right: 9px;
    top: -16px;
  }
</style>

<template>
  <div>
    <div class="pa3 box-monica bg-white mb3">
      <form-textarea class="mb3" v-model="newNote.body" placeholder="test" v-on:content-change="newNote.body = $event"></form-textarea>
      <div class="tr">
        <a class="btn dib no-color no-underline pv2 ph3 mr2" href="">Cancel</a>
        <a class="btn dib add no-color no-underline pv2 ph3" @click.prevent="store">Add</a>
      </div>
    </div>

    <div class="box-monica bg-white mb3" v-for="note in notes" :key="note.id">
      <div class="pa3 border-bottom" v-html="note.parsed_body"></div>
      <div class="ph3 pv2 note-information">
        <svg class="mr1" style="fill: #888282;" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M1 4c0-1.1.9-2 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4zm2 2v12h14V6H3zm2-6h2v2H5V0zm8 0h2v2h-2V0zM5 9h2v2H5V9zm0 4h2v2H5v-2zm4-4h2v2H9V9zm0 4h2v2H9v-2zm4-4h2v2h-2V9zm0 4h2v2h-2v-2z"/></svg>
        {{ note.created_at_short }}
        <span class="fr relative">
          <a class="no-color no-underline relative pointer" @click.prevent="menuId = note.id">
            <img src="/img/box/ellipsis.svg" alt="">
          </a>
          <div class="absolute br2 bg-white z-max tl pv2 ph3 bounceIn faster note-information-menu" v-if="menuId == note.id">
            <ul class="list ma0 pa0">
              <li class="pv2">
                <a class="no-color no-underline" href="">
                  {{ $t('app.edit') }}
                </a>
              </li>
              <li class="pv2">
                <a class="delete no-underline" @click.prevent="trash(note)">
                  {{ $t('app.delete') }}
                </a>
              </li>
            </ul>
          </div>
        </span>
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
            menuId: 0,

            editMode: false,

            newNote: {
                body: '',
            },

            dirltr: true,
        };
    },

    mounted() {
        this.prepareComponent();
    },

    created() {
      window.addEventListener('click', this.closeMenu);
    },

    beforeDestroy() {
      window.removeEventListener('click', this.closeMenu);
    },

    methods: {
        closeMenu(e) {
          if (!this.$el.contains(e.target)) {
            this.menuId = 0;
          }
        },

        prepareComponent() {
            this.dirltr = this.$root.htmldir == 'ltr';
            this.get();
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

        get() {
            axios.get('/people/' + this.hash + '/notes')
                .then(response => {
                    this.notes = response.data;
                });
        },

        store() {
            axios.post('/people/' + this.hash + '/notes', this.newNote)
                .then(response => {
                    this.newNote.body = '';
                    this.notes.push(response.data);
                });
        },

        toggleFavorite(note) {
            axios.post('/people/' + this.hash + '/notes/' + note.id + '/toggle')
                .then(response => {
                    this.get();
                });
        },

        update(note) {
            axios.put('/people/' + this.hash + '/notes/' + note.id, note)
                .then(response => {
                    Vue.set(note, 'edit', note.edit);
                    this.get();
                });
        },

        trash(note) {
          axios.delete('/people/' + this.hash + '/notes/' + note.id)
            .then(response => {
              this.notes.splice(this.notes.indexOf(note), 1);
            });
        },
    }
};
</script>
