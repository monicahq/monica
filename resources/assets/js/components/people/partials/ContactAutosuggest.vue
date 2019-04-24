<style lang="scss">
  .autosuggest__results {
    position: absolute;
    width: 100%;
    z-index: 10;
    overflow: scroll;
    max-height: 361px;
  }
  .autosuggest__results_item {
    background: white;
  }
  .autosuggest__results .autosuggest__results_item:active,
  .autosuggest__results .autosuggest__results_item:hover,
  .autosuggest__results .autosuggest__results_item:focus,
  .autosuggest__results .autosuggest__results_item.autosuggest__results_item-highlighted {
    background: #f5f5f5;
  }
</style>

<template>
  <div>
    <p v-if="title" class="mb2" :class="{ b: required }">
      {{ title }}
    </p>
    <vue-autosuggest
      ref="autosuggest"
      :suggestions="items"
      :input-props="inputProps"
      :get-suggestion-value="getSuggestionValue"
      @selected="selectHandler"
      @click="clickHandler"
      @blur="blurHandler"
    >
      <template slot-scope="{suggestion}">
        <component :is="componentItem" :item="suggestion.item" />
      </template>
      <!--
      <template slot="footer">
        <div class="autosuggest__results_item" v-if="addNoResult">
          <component :is="componentItem" :item="{}"></component>
        </div>
      </template>
      -->
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
    inputProps() {
      return {
        id: this.id ? this.id : 'autosuggest__input',
        onInputChange: this.updateItems,
        placeholder: this.placeholder,
        class: ['form-control', this.inputClass],
      };
    }
  },

  mounted() {
    this.callUpdateItems = _.debounce((text) => {
      this.getContacts(text, this)
        .then((response) => {
          return { data: response };
        })
        .then((item) => {
          this.cache[text] = item;
          this.items = [item];
        });
    }, this.wait);
  },

  methods: {

    updateItems (text) {
      if (text === null) {
        return;
      }
      if (text.length < this.minLen) {
        this.items = [];
        return;
      }

      if (this.cache[text] !== undefined) {
        this.callUpdateItems.cancel();
        var item = this.cache[text];
        this.items = [item];
      } else {
        this.callUpdateItems(text);
      }
    },

    getContacts: function (keyword, vm) {
      return axios.post('/people/search', {
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
            .filter(vm.filter)
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
      //this.items = [];
      //this.$refs.autosuggest.searchInput = '';
      this.$emit('blur', sender);
    },

    clickHandler(e) {
      this.loading = false;
      this.updateItems(e ? e.item.name : this.$refs.autosuggest.searchInput);
    },

    selectHandler(suggestion) {
      if (!suggestion || !suggestion.item) {
        return;
      }

      this.$emit('select', suggestion);

      this.$refs.autosuggest.searchInput = '';
    },

    getSuggestionValue(suggestion) {
      if (!suggestion || !suggestion.item) {
        return;
      }
      return suggestion.item.complete_name.length > 0 ?
        suggestion.item.complete_name :
        suggestion.item.keyword;
    }
  }
};
</script>
