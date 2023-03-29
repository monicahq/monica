<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-4xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form
          class="mb-8 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="search">
          <div
            class="section-head border-b border-gray-200 bg-blue-50 p-5 dark:border-gray-700 dark:bg-blue-900 dark:bg-blue-900">
            <h1 class="text-center text-2xl font-medium">Search something in the vault</h1>
          </div>
          <div class="p-5">
            <text-input
              ref="searchField"
              v-model="form.searchTerm"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :placeholder="'Type something'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @keyup="search" />
          </div>
        </form>

        <!-- search results -->
        <div v-if="!processingSearch && Object.keys(results).length !== 0">
          <contact :data="results.contacts" />

          <group :data="results.groups" />

          <note :data="results.notes" />
        </div>

        <!-- searching results -->
        <div
          v-if="processingSearch"
          class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900">
          <loading />
        </div>

        <!-- not enough characters -->
        <div
          v-if="form.searchTerm.length < 3"
          class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900">
          <p>Please enter at least 3 characters to initiate a search.</p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Contact from '@/Pages/Vault/Search/Partials/Contact.vue';
import Note from '@/Pages/Vault/Search/Partials/Note.vue';
import Group from '@/Pages/Vault/Search/Partials/Group.vue';

export default {
  components: {
    Layout,
    TextInput,
    Contact,
    Note,
    Group,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      processingSearch: false,
      form: {
        searchTerm: '',
        errors: [],
      },
      results: [],
    };
  },

  mounted() {
    this.$nextTick(() => {
      this.$refs.searchField.focus();
    });
  },

  methods: {
    search: _.debounce(function () {
      if (this.form.searchTerm != '' && this.form.searchTerm.length >= 3) {
        this.processingSearch = true;

        axios
          .post(this.data.url.search, this.form)
          .then((response) => {
            this.results = response.data.data;
            this.processingSearch = false;
          })
          .catch((error) => {
            this.form.errors = error.response.data;
            this.processingSearch = false;
          });
      } else {
        this.results = [];
      }
    }, 300),
  },
};
</script>

<style lang="scss" scoped></style>
