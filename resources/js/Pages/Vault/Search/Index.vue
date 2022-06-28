<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
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
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-4xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <form class="mb-8 rounded-lg border border-gray-200 bg-white" @submit.prevent="search">
          <div class="section-head border-b border-gray-200 bg-blue-50 p-5">
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
          class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
          <loading />
        </div>

        <!-- not enough characters -->
        <div
          v-if="form.searchTerm.length < 3"
          class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
          <p>Please enter at least 3 characters to initiate a search.</p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import TextInput from '@/Shared/Form/TextInput';
import Contact from '@/Pages/Vault/Search/Partials/Contact';
import Note from '@/Pages/Vault/Search/Partials/Note';
import Group from '@/Pages/Vault/Search/Partials/Group';

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
