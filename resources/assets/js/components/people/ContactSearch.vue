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
        window.location = 'people/add';
      }
    },

  }
};
</script>
