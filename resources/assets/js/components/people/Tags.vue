<style scoped>
.autocomplete-results {
    width: 150px;
}

.autocomplete-result.is-active,
  .autocomplete-result:hover {
    background-color: #4AAE9B;
    color: white;
  }
</style>

<template>
  <div class="tc">
    <div v-show="editMode" class="mb3">
      <div class="relative di mr2">
        <input v-model="search"
               type="text"
               class="di br2 f5 ba b--black-40 pa2 outline-0"
               :placeholder="$t('people.tag_add_search')"
               @keydown.down="onArrowDown"
               @keydown.up="onArrowUp"
               @keydown.enter="onEnter"
               @keydown.esc="onEscape"
               @input="onChange"
        />

        <ul v-show="isOpen" class="autocomplete-results ba b--gray-monica absolute bg-white left-0 z-9999">
          <li v-for="(result, i) in results"
              :key="i"
              class="autocomplete-result"
              :class="{ 'is-active': i === arrowCounter }"
              @click="setResult(result)"
          >
            {{ result.name }}
          </li>
        </ul>
      </div>

      <a class="pointer" @click="editMode = false">
        {{ $t('app.cancel') }}
      </a>
      <a class="pointer" @click="store()">
        {{ $t('app.save_close') }}
      </a>
    </div>

    <ul>
      <li v-for="tag in contactTags" :key="tag.id" class="di mr2">
        <span class="bg-white ph2 pb1 pt0 dib br3 b--light-gray ba mb2">
          <span v-show="!editMode" class="pointer" @click="navigateTo(tag)">
            {{ tag.name }}
          </span>
          <span v-show="editMode">
            {{ tag.name }}
          </span>
          <span v-show="editMode" class="pointer" @click="removeTag(tag)">
            ×
          </span>
        </span>
      </li>
      <li v-show="contactTags.length > 0" class="di">
        <a v-show="!editMode" class="pointer" @click="editMode = true">
          {{ $t('app.edit') }}
        </a>
      </li>
      <li v-show="contactTags.length == 0" class="di">
        <span class="i mr2">
          {{ $t('people.tag_no_tags') }}
        </span>
        <a v-show="!editMode" class="pointer" @click="editMode = true">
          {{ $t('people.tag_add') }}
        </a>
      </li>
    </ul>
  </div>
</template>

<script>
import moment from 'moment';

export default {

    props: {
        hash: {
            type: String,
            default: '',
        },
    },
    data() {
        return {
            allTags: [],
            availableTags: [],
            contactTags: [],
            editMode: false,
            search: '',
            results: [],
            isOpen: false,
            arrowCounter: 0,
            dirltr: true,
        };
    },

    mounted() {
        this.dirltr = this.$root.htmldir == 'ltr';
        this.prepareComponent();
        document.addEventListener('click', this.handleClickOutside);
    },

    destroyed() {
        document.removeEventListener('click', this.handleClickOutside);
    },

    methods: {
        prepareComponent() {
            this.getExistingTags();
            this.getContactTags();
        },

        getExistingTags() {
            axios.get('/tags')
                .then(response => {
                    this.allTags = response.data.data;
                });
        },

        getContactTags() {
            axios.get('/people/' + this.hash + '/tags')
                .then(response => {
                    this.contactTags = response.data.data;
                });
        },

        removeTag(tag) {
            this.contactTags.splice(this.contactTags.indexOf(tag), 1);
        },

        onChange() {
            this.isOpen = true;
            this.filterResults();
        },

        onEnter() {
            if (this.search != '') {
                this.contactTags.push({
                    id: moment().format(), // we just need a random ID here
                    name: this.search
                });
                this.arrowCounter = -1;
                this.isOpen = false;
                this.search = null;
            }
        },

        onArrowDown() {
            if (this.arrowCounter < this.results.length) {
                this.arrowCounter = this.arrowCounter + 1;
                this.search = this.results[this.arrowCounter].name;
            }
        },

        onArrowUp() {
            if (this.arrowCounter > 0) {
                this.arrowCounter = this.arrowCounter - 1;
                this.search = this.results[this.arrowCounter].name;
            }
        },

        onEscape() {
            this.arrowCounter = -1;
            this.isOpen = false;
            this.search = null;
        },

        setResult(result) {
            this.search = null;
            this.isOpen = false;
            this.contactTags.push(result);
        },

        filterResults() {
            this.results = this.allTags.filter(item => item.name.toLowerCase().indexOf(this.search.toLowerCase()) > -1);
        },

        filterAllTags() {
            var me = this.contactTags;
            this.availableTags = this.allTags.filter((item) => {
                return !me.includes(item);
            });
        },

        store() {
            this.editMode = false;
            axios.post('/people/' + this.hash + '/tags/update', this.contactTags)
                .then(response => {
                    this.getExistingTags();
                });
        },

        navigateTo(tag) {
            window.location.href = '/people?tag1=' + tag.name_slug;
        },

        handleClickOutside(evt) {
            if (!this.$el.contains(evt.target)) {
                this.isOpen = false;
                this.arrowCounter = -1;
            }
        }
    }
};
</script>
