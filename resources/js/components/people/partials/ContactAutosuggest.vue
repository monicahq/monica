<style lang="scss">
  .autosuggest__results-container {
    position: relative;
    width: 100%;
  }
  .autosuggest__results {
    position: absolute;
    width: 100%;
    z-index: 100;
  }
  .autosuggest__results-overflow {
    position: absolute;
    width: 100%;
    z-index: 100;
    overflow: scroll;
    max-height: 361px;
  }
  .autosuggest__results-item {
    background: white;
  }
  .autosuggest__results-item:active,
  .autosuggest__results-item:hover,
  .autosuggest__results-item:focus,
  .autosuggest__results-item--highlighted {
    background: #f5f5f5;
  }
</style>

<template>
  <div>
    <label
      v-if="title"
      class="mb2"
      :class="{ b: required }"
      :for="realid"
    >
      {{ title }}
    </label>
    <vue-autosuggest
      ref="autosuggest"
      :suggestions="items"
      :input-props="inputProps"
      :get-suggestion-value="getSuggestionValue"
      :component-attr-class-autosuggest-results="overflow ? 'autosuggest__results-overflow' : 'autosuggest__results'"
      @selected="selectHandler"
      @click="clickHandler"
      @blur="blurHandler"
      @input="updateItems"
    >
      <template slot-scope="{suggestion}">
        <component :is="componentItem" :item="suggestion.item" />
      </template>
    </vue-autosuggest>
  </div>
</template>

<script>
import axios from 'axios';
import { VueAutosuggest } from 'vue-autosuggest';

export default {

  components: {
    VueAutosuggest,
  },
  props: {
    id: {
      type: String,
      default: null,
    },
    title: {
      type: String,
      default: null,
    },
    required: {
      type: Boolean,
      default: true,
    },
    addNoResult: {
      type: Boolean,
      default: true,
    },
    placeholder : {
      type: String,
      default: '',
    },
    componentItem : {
      type: Object,
      default: () => null
    },
    wait : {
      type: Number,
      default: 200,
    },
    minLen : {
      type: Number,
      default: 1,
    },
    overflow: {
      type: Boolean,
      default: false,
    },
    inputClass : {
      type: String,
      default: '',
    },
    filter: {
      type: Function,
      default: () => true,
    }
  },

  data () {
    return {
      items: [],
      callUpdateItems: null,
      cache: [],
    };
  },

  computed: {
    realid() {
      return this.id ? this.id : 'autosuggest__input';
    },
    inputProps() {
      return {
        id: this.realid,
        placeholder: this.placeholder,
        class: ['form-control', this.inputClass],
      };
    }
  },

  mounted() {
    this.callUpdateItems = _.debounce((text) => {
      this.getContacts(text, this)
        .then((response) => {
          this.cache[text] = response;
          this.displayItems(text);
        });
    }, this.wait);
  },

  methods: {

    updateItems (text) {
      if (text === null || text === undefined) {
        return;
      }
      if (text.length < this.minLen) {
        this.items = [];
        return;
      }

      if (this.cache[text] === undefined) {
        this.callUpdateItems(text);
      } else {
        this.callUpdateItems.cancel();
        this.displayItems(text);
      }
    },

    displayItems (text) {
      var datas = this.cache[text];

      datas = datas.filter(this.filter);
      if (datas.length === 0) {
        datas = [{
          id: -1,
          name: 'no_result',
          keyword: text,
        }];
      }

      this.items = [{ data: datas }];
    },

    getContacts: function (keyword, vm) {
      return axios.post('people/search', {
        needle: keyword
      }).then(function(response) {
        let data = [];
        if (response.data.noResults != null) {
          data.push({
            id: -1,
            name: 'no_result',
            keyword: keyword,
          });
        } else {
          response.data.data
            .forEach(function (contact) {
              contact.keyword = keyword;
              data.push(contact);
            });
        }
        if (! vm.addNoResult && data.length === 0) {
          data.push({
            id: -1,
            name: 'no_result',
            keyword: keyword,
          });
        }
        return data;
      });
    },

    clearCache() {
      this.cache = [];
      this.items = [];
    },

    blurHandler(sender) {
      this.$emit('blur', sender);
    },

    clickHandler(e) {
      this.loading = false;
      this.updateItems(this.value);
    },

    selectHandler(suggestion) {
      if (!suggestion || !suggestion.item) {
        return;
      }

      this.$emit('select', suggestion);

      this.$refs.autosuggest.searchInput = '';
    },

    getSuggestionValue(suggestion) {
      if (!suggestion || !suggestion.item || suggestion.item.id < 0) {
        return;
      }
      return suggestion.item.complete_name.length > 0 ?
        suggestion.item.complete_name :
        suggestion.item.keyword;
    }
  }
};
</script>
