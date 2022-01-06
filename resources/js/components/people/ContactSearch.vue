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
    formNameOrder : {
      type: String,
      default: 'firstname',
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
        // contact with ID = -1 is the 'add person' contact

        const keyword = contact.item.keyword.trim();
        let names, email;

        // attempt to extract name and email from 'first last <email@example.com>' format
        // https://stackoverflow.com/questions/9558608/regex-for-parsing-name-and-email-from-a-single-string
        const emailAndNameMatch = keyword.match(/(.*[^\s*<])?\s*<(.*)>/);

        if(emailAndNameMatch === null) {
          names = keyword;
        } else {
          names = emailAndNameMatch[1];
          email = emailAndNameMatch[2];
        }

        names = names.split(' ').map(name => _.capitalize(name));

        let first_name, last_name;
        if (this.formNameOrder === 'firstname') {
          first_name = names[0];
          last_name = names.slice(1).join(' ');
        } else {
          first_name = names.slice(1).join(' ');
          last_name = names[0];
        }

        const params = new URLSearchParams();
        if (first_name) {
          params.set('first_name', first_name);
        }
        if (last_name) {
          params.set('last_name', last_name);
        }
        if (email) {
          params.set('email', email);
        }
        let p = params.toString();

        window.location = 'people/add' + (p !== '' ? '?' + p : '');
      }
    },

  }
};
</script>
