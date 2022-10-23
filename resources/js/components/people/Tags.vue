<style scoped>
.autocomplete-results {
    width: 150px;
}

.autocomplete-result.is-active,
  .autocomplete-result:hover {
    background-color: #4AAE9B;
    color: white;
  }

.tag-link,
.tag-link:hover{
    text-decoration: none;
}
</style>

<template>
  <div class="tc">
    <!-- list of existing tags -->
    <ul>
      <li v-for="tag in contactTags" :key="tag.id" class="di mr2">
        <a v-if="!editMode" :href="`people?tags[]=${encodeURIComponent(tag.name)}`" class="tag-link bg-white ph2 pb1 pt0 dib br3 b--light-gray ba mb2">
          {{ tag.name }}
        </a>
        <span v-else class="bg-white ph2 pb1 pt0 dib br3 b--light-gray ba mb2">
          <span>
            {{ tag.name }}
          </span>
          <span class="pointer" @click="removeTag(tag)">
            ×
          </span>
        </span>
      </li>

      <!-- edit button -->
      <li v-show="contactTags.length > 0" class="di">
        <a v-show="!editMode" class="pointer" href="" @click.prevent="enterEditMode">
          {{ $t('app.edit') }}
        </a>
      </li>

      <!-- add a new tag -->
      <li v-show="editMode" class="di mb3">
        <div class="relative di mr2">
          <input ref="tags"
                 v-model="search"
                 type="text"
                 class="di br2 f5 ba b--black-40 pa2 outline-0"
                 :placeholder="$t('people.tag_add_search')"
                 @keydown.down="onArrowDown"
                 @keydown.up="onArrowUp"
                 @keydown.enter="onEnter"
                 @keydown.esc="onEscape"
                 @input="onChange"
          />

          <ul v-show="isOpen" v-if="results.length > 0" class="autocomplete-results ba b--gray-monica absolute bg-white left-0 z-9999">
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

        <a class="pointer" href="" @click.prevent="search = ''; editMode = false; isOpen = false;">
          {{ $t('app.close') }}
        </a>
      </li>

      <!-- case of no tags -->
      <li v-show="contactTags.length === 0 && !editMode" class="di">
        <span class="i mr2">
          {{ $t('people.tag_no_tags') }}
        </span>
        <a v-show="!editMode" class="pointer" href="" @click.prevent="enterEditMode">
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
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  mounted() {
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
      axios.get('tags')
        .then(response => {
          this.allTags = response.data.data;
        });
    },

    getContactTags() {
      axios.get('people/' + this.hash + '/tags')
        .then(response => {
          this.contactTags = response.data.data;
        });
    },

    enterEditMode() {
      this.editMode = true;
      this.$nextTick(() => this.$refs.tags.focus());
    },

    removeTag(tag) {
      this.contactTags.splice(this.contactTags.indexOf(tag), 1);
      this.store();
    },

    onChange() {
      this.isOpen = true;
      this.filterResults();
    },

    onEnter() {
      if (this.search !== '') {
        this.contactTags.push({
          id: moment().format(), // we just need a random ID here
          name: this.search
        });
        this.arrowCounter = -1;
        this.isOpen = false;
        this.search = '';
        this.store();
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
      this.search = '';
    },

    setResult(result) {
      this.search = '';
      this.isOpen = false;
      this.contactTags.push(result);
      this.store();
    },

    filterResults() {
      var me = this.contactTags;
      var search = _.toLower(this.search);
      this.results = this.allTags.filter(item => _.toLower(item.name).indexOf(search) > -1
                                                  && _.findIndex(me, t => t.name === item.name) < 0);
    },

    filterAllTags() {
      var me = this.contactTags;
      this.availableTags = this.allTags.filter((item) => {
        return !me.includes(item);
      });
    },

    store() {
      axios.post('people/' + this.hash + '/tags/update', this.contactTags)
        .then(response => {
          this.getExistingTags();
        });
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
