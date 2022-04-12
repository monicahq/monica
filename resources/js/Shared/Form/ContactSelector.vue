<style lang="scss" scoped>
.optional-badge {
  border-radius: 4px;
  color: #283e59;
  background-color: #edf2f9;
  padding: 1px 3px;
}

.icon-search {
  left: 8px;
  top: 13px;
}

.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>

<template>
  <div :class="divOuterClass">
    <!-- input -->
    <div class="mb-2">
      <label v-if="label" class="mb-2 block text-sm" :for="id">
        {{ label }}
        <span v-if="!required" class="optional-badge text-xs"> optional </span>
      </label>

      <!-- list of selected contacts -->
      <ul v-if="localContacts.length > 0" class="mb-4 rounded-lg border border-gray-200 bg-white">
        <li
          v-for="contact in localContacts"
          :key="contact.id"
          class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50">
          <inertia-link :href="contact.url">
            {{ contact.name }}
          </inertia-link>

          <!-- actions -->
          <ul class="text-sm">
            <li class="inline cursor-pointer text-sky-500 hover:text-blue-900" @click="remove(contact)">Remove</li>
          </ul>
        </li>
      </ul>

      <p
        v-if="displayAddContactButton"
        @click="showAddContactMode"
        class="mr-2 mb-3 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300">
        + Add a contact
      </p>
    </div>

    <!-- mode to add a contact -->
    <div v-if="addContactMode">
      <div class="relative mb-2">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="icon-search absolute h-4 w-4 text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>

        <input
          :ref="'search'"
          :class="localInputClasses"
          v-model="form.searchTerm"
          :type="type"
          :name="name"
          :required="required"
          :placeholder="placeholder"
          @keyup="search"
          @keydown.esc="sendEscKey" />
        <span v-if="maxlength && displayMaxLength" class="length absolute rounded text-xs">
          {{ charactersLeft }}
        </span>
      </div>

      <!-- blank state - case where we suggest most contacted contacts in the vault -->
      <div
        v-if="
          localContacts.length == 0 &&
          displayMostConsultedContacts &&
          searchResults.length == 0 &&
          form.searchTerm.length == 0
        "
        class="mb-6">
        <p class="mb-2 mt-2 text-center text-sm text-gray-600">Maybe one of these contacts?</p>
        <ul class="rounded-lg border border-gray-200 bg-white">
          <li
            v-for="contact in mostConsultedContacts"
            :key="contact.id"
            class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50">
            <inertia-link :href="contact.url" class="text-sky-500 hover:text-blue-900">{{ contact.name }}</inertia-link>
            <!-- actions -->
            <ul class="text-sm">
              <li class="inline cursor-pointer text-sky-500 hover:text-blue-900" @click="add(contact)">Add</li>
            </ul>
          </li>
        </ul>
      </div>

      <!-- searching results -->
      <div
        v-if="processingSearch"
        class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
        <p>Searching...</p>
      </div>

      <!-- not enough characters -->
      <div
        v-if="form.searchTerm.length < 3 && form.searchTerm.length != 0"
        class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
        <p>Please enter at least 3 characters to initiate a search.</p>
      </div>

      <!-- search results: results found -->
      <div v-if="searchResults.length != 0 && form.searchTerm.length != 0" class="mb-3">
        <errors :errors="form.errors" />

        <ul class="mb-4 rounded-lg border border-gray-200 bg-white">
          <li
            v-for="contact in searchResults"
            :key="contact.id"
            class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50">
            <inertia-link :href="contact.url">
              {{ contact.name }}
            </inertia-link>

            <!-- actions -->
            <ul class="text-sm">
              <li class="inline cursor-pointer text-sky-500 hover:text-blue-900" @click="add(contact)">Add</li>
            </ul>
          </li>
        </ul>
      </div>

      <!-- search results: no results found -->
      <div
        v-if="searchResults.length == 0 && form.searchTerm.length >= 3"
        class="mb-3 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
        <p>No search results</p>
      </div>
    </div>
  </div>
</template>

<script>
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    Errors,
  },

  props: {
    inputClass: {
      type: String,
      default: '',
    },
    divOuterClass: {
      type: String,
      default: '',
    },
    placeholder: {
      type: String,
      default: 'Find a contact in this vault',
    },
    label: {
      type: String,
      default: '',
    },
    type: {
      type: String,
      default: 'text',
    },
    required: {
      type: Boolean,
      default: false,
    },
    displayMostConsultedContacts: {
      type: Boolean,
      default: false,
    },
    addMultipleContacts: {
      type: Boolean,
      default: false,
    },
    searchUrl: {
      type: String,
      default: '',
    },
    mostConsultedContactsUrl: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      addContactMode: false,
      processingSearch: false,
      localInputClasses: '',
      localContacts: [],
      mostConsultedContacts: [],
      searchResults: [],
      form: {
        searchTerm: '',
        contactsId: [],
        errors: [],
      },
    };
  },

  created() {
    this.localInputClasses =
      'border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm disabled:bg-slate-50 pl-8 w-full' +
      this.inputClass;

    if (this.displayMostConsultedContacts) {
      this.lookupMostConsultedContacts();
    }
  },

  computed: {
    displayAddContactButton: function () {
      if (!this.addMultipleContacts && this.localContacts.length >= 1) {
        return false;
      }

      if (!this.addContactMode) {
        return true;
      }
    },
  },

  methods: {
    showAddContactMode() {
      this.addContactMode = true;
      this.$nextTick(() => {
        this.$refs.search.focus();
      });
    },

    sendEscKey() {
      this.addContactMode = false;
    },

    lookupMostConsultedContacts() {
      axios
        .get(this.mostConsultedContactsUrl)
        .then((response) => {
          this.mostConsultedContacts = response.data.data;
        })
        .catch((error) => {});
    },

    add(contact) {
      var id = this.localContacts.findIndex((x) => x.id === contact.id);

      if (id == -1) {
        this.localContacts.push(contact);
        this.form.contactsId.push(contact.id);
        this.form.searchTerm = '';
        this.addContactMode = false;
      }
    },

    remove(contact) {
      var id = this.localContacts.findIndex((existingContact) => existingContact.id === contact.id);
      this.localContacts.splice(id, 1);

      var id = this.form.contactsId.findIndex(contact.id);
      this.form.contactsId.splice(id, 1);
    },

    search: _.debounce(function () {
      if (this.form.searchTerm != '' && this.form.searchTerm.length >= 3) {
        this.processingSearch = true;

        axios
          .post(this.searchUrl, this.form)
          .then((response) => {
            this.searchResults = _.filter(response.data.data, (contact) =>
              _.every(this.localContacts, (e) => contact.id !== e.id),
            );
            this.processingSearch = false;
          })
          .catch((error) => {
            this.form.errors = error.response.data;
            this.processingSearch = false;
          });
      } else {
        this.searchResults = [];
      }
    }, 300),
  },
};
</script>
