<template>
  <div>
    <p v-if="title" class="mb2" :class="{ b: required }">
      {{ title }}
    </p>
    <input type="hidden" :name="name" :value="selected ? selected.id : ''" />
    <v-select v-model="selected" :placeholder="placeholder" :label="'complete_name'" :options="computedOption" @search="search" />
  </div>
</template>

<script>
import vSelect from 'vue-select';
import axios from 'axios';

export default {
  components: {
    vSelect
  },
  props: {
    name: {
      type: String,
      default: '',
    },
    title: {
      type: String,
      default: '',
    },
    required: {
      type: Boolean,
      default: true,
    },
    userContactId: {
      type: String,
      default: '',
    },
    defaultOptions: {
      type: Array,
      default: function () {
        return [];
      }
    },
    placeholder: {
      type: String,
      default: '',
    }
  },

  data () {
    return {
      src : 'people/search',
      filterable : false,
      selected: null,
      newOptions: [],
    };
  },

  computed: {
    computedOption : function() {
      return this.newOptions.length > 0 ? this.newOptions : this.defaultOptions;
    }
  },

  methods: {
    search(keyword, loading) {
      this.getContacts(keyword, loading, this);
    },

    getContacts: function (keyword, loading, vm) {
      axios.post(this.src, {
        needle: keyword,
        accountId: $('body').attr('data-account-id')
      }).then(function(response) {
        let data = [];
        response.data.data.forEach(function (contact) {
          if (contact.id === vm.userContactId) {
            return;
          }
          data.push(contact);
        });

        vm.newOptions = data;
      });
    }
  }
};
</script>
