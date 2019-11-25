<style lang="scss" >
</style>

<template>
  <div>
    <contact-autosuggest
      :id="id"
      ref="contactauto"
      :title="title"
      :required="required"
      :placeholder="placeholder"
      :component-item="componentItem"
      :filter="filter"
      :add-no-result="false"
      :input-class="'user-input-search-input'"
      :overflow="true"
      @select="select"
    />
    <ul class="contacts mt2">
      <ul class="contacts-list table">
        <li v-for="contact in items" :key="contact.id" class="table-row">
          <div class="table-cell w-80">
            <strong>{{ contact.complete_name }}</strong>
          </div>
          <div class="table-cell actions">
            <a class="pointer" href="" @click.prevent="remove(contact)">
              {{ $t('app.delete') }}
            </a>
          </div>

          <input type="hidden" name="contacts[]" :value="contact.id" />
        </li>
      </ul>
    </ul>
  </div>
</template>

<script>
import ContactAutosuggest from './partials/ContactAutosuggest.vue';
import ContactMultiItem from './partials/ContactMultiItem.vue';

export default {

  components: {
    ContactAutosuggest
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
    placeholder : {
      type: String,
      default: '',
    },
    userContactId: {
      type: Number,
      default: 0,
    },
    contacts: {
      type: Array,
      default: () => []
    }
  },

  data() {
    return {
      items: [],
    };
  },

  computed: {
    componentItem() {
      return ContactMultiItem;
    }
  },

  mounted() {
    this.items = this.contacts;
  },

  methods: {
    filter(item) {
      return _.findIndex(this.items, i => { return i.id === item.id; }) < 0;
    },

    select(contact) {
      if (contact.item && contact.item.id > 0 && this.filter(contact.item)) {
        this.items.push(contact.item);
      }
    },

    remove(contact) {
      this.items.splice(this.items.indexOf(contact), 1);
    }
  }
};
</script>
