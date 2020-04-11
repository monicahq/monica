<style lang="scss" >
</style>

<template>
  <div>
    <contact-autosuggest
      :title="title"
      :required="required"
      :placeholder="placeholder"
      :component-item="componentItem"
      :input-class="'header-search-input'"
      @select="select"
    />
  </div>
</template>

<script>
import ContactAutosuggest from './partials/ContactAutosuggest.vue';
import ContactItem from './partials/ContactItem.vue';

export default {

  components: {
    ContactAutosuggest
  },
  props: {
    title: {
      type: String,
      default: null,
    },
    required: {
      type: Boolean,
      default: true,
    },
    placeholder : {
      type: String,
      default: '',
    },
  },

  computed: {
    componentItem() {
      return ContactItem;
    }
  },

  methods: {
    select(contact) {
      if (contact.item.id > 0) {
        window.location = contact.item.route;
      } else {
        const names = contact.item.keyword.split(' ').map(name =>
          name.charAt(0).toUpperCase() + name.substr(1).toLowerCase()
        );
        const first_name = names[0];
        const last_name = names.slice(1).join(' ');
        
        let params = new URLSearchParams();
        if (first_name)
          params.set('first_name', first_name);
        if (last_name)
          params.set('last_name', last_name);

        window.location = 'people/add' + (params != "" ? '?' + params : "");
      }
    },

  }
};
</script>
