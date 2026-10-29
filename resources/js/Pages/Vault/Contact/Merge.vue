<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, nextTick, useTemplateRef } from 'vue';
import Layout from '@/Layouts/Layout.vue';
import { ScanSearch } from 'lucide-vue-next';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const searchInput = useTemplateRef('searchInput');
const processingSearch = ref(false);
const searchResults = ref([]);
const selectedContact = ref(null);
const merging = ref(false);

const form = useForm({
  searchTerm: '',
  errors: [],
});

const mergeForm = useForm({
  duplicate_contact_id: '',
});

const search = (() => {
  if (form.searchTerm !== '' && form.searchTerm.length >= 3) {
    processingSearch.value = true;

    axios
      .post(props.data.url.search, form)
      .then((response) => {
        searchResults.value = response.data.data.filter((contact) => contact.id !== props.data.contact.id);
        processingSearch.value = false;
      })
      .catch((error) => {
        form.errors = error.response.data;
        processingSearch.value = false;
      });
  } else {
    searchResults.value = [];
  }
});

const selectContact = (contact) => {
  selectedContact.value = contact;
  mergeForm.duplicate_contact_id = contact.id;
  form.searchTerm = '';
  searchResults.value = [];
};

const clearSelection = () => {
  selectedContact.value = null;
  mergeForm.duplicate_contact_id = '';
};

const submitMerge = () => {
  if (!selectedContact.value) {
    return;
  }

  merging.value = true;
  router.post(props.data.url.merge, mergeForm, {
    onFinish: () => {
      merging.value = false;
    },
  });
};
</script>

<template>
  <Layout :layout-data="layoutData" :inside-vault="true">
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <Link :href="layoutData.vault.url.contacts" class="text-blue-500 hover:underline">
                {{ $t('Contacts') }}
              </Link>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="me-2 inline">
              <Link :href="data.url.back" class="text-blue-500 hover:underline">
                {{ data.contact.name }}
              </Link>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">
              {{ $t('Merge with another contact') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <h2 class="mb-6 text-center text-lg">
          {{ $t('Merge :name with another contact', { name: data.contact.name }) }}
        </h2>

        <div class="mb-6 rounded-xs border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <div
            class="flex rounded-t border-b border-gray-200 bg-slate-50 px-3 py-2 dark:border-gray-700 dark:bg-slate-900">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 grow pe-2"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>

            <p>
              {{
                $t(
                  'Merging contacts will combine all data from both contacts into one. The contact you select below will be merged into :name. This action cannot be undone.',
                  { name: data.contact.name },
                )
              }}
            </p>
          </div>

          <div class="p-5">
            <div v-if="selectedContact" class="mb-4">
              <div class="mb-2 text-sm font-medium">
                {{ $t('Selected contact to merge:') }}
              </div>
              <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-slate-50 px-4 py-3 dark:border-gray-700 dark:bg-slate-900">
                <span class="font-medium">{{ selectedContact.name }}</span>
                <button
                  type="button"
                  class="text-sm text-blue-500 hover:underline"
                  @click="clearSelection">
                  {{ $t('Change') }}
                </button>
              </div>
            </div>

            <div v-if="!selectedContact">
              <label class="mb-2 block text-sm font-medium">
                {{ $t('Search for a contact to merge with') }}
              </label>
              <div class="relative mb-3">
                <ScanSearch class="absolute start-2 top-2 h-4 w-4 text-gray-400" />

                <input
                  ref="searchInput"
                  v-model="form.searchTerm"
                  class="ps-8 w-full rounded-md shadow-xs bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-700 focus:border-indigo-300 focus:ring-3 focus:ring-indigo-200/50"
                  type="text"
                  :placeholder="$t('Enter at least 3 characters to search...')"
                  @input="search" />
              </div>

              <div
                v-if="processingSearch"
                class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900">
                <p>{{ $t('Searching…') }}</p>
              </div>

              <div
                v-if="form.searchTerm.length < 3 && form.searchTerm.length !== 0"
                class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900">
                <p>{{ $t('Please enter at least 3 characters to initiate a search.') }}</p>
              </div>

              <div v-if="searchResults.length !== 0 && form.searchTerm.length !== 0">
                <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                  <li
                    v-for="contact in searchResults"
                    :key="contact.id"
                    class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                    <span>{{ contact.name }}</span>
                    <button
                      type="button"
                      class="text-sm text-blue-500 hover:underline"
                      @click="selectContact(contact)">
                      {{ $t('Select') }}
                    </button>
                  </li>
                </ul>
              </div>

              <div
                v-if="searchResults.length === 0 && form.searchTerm.length >= 3"
                class="mb-3 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900">
                <p>{{ $t('No results found') }}</p>
              </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-4">
              <Link :href="data.url.back" class="text-gray-500 hover:underline">
                {{ $t('Cancel') }}
              </Link>
              <button
                type="button"
                :disabled="!selectedContact || merging"
                :class="{
                  'opacity-25': !selectedContact || merging,
                }"
                class="rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700 focus:bg-gray-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-white dark:focus:bg-white dark:focus:ring-offset-gray-800 dark:active:bg-gray-300"
                @click="submitMerge">
                {{ merging ? $t('Merging...') : $t('Merge contacts') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </Layout>
</template>

<style lang="scss" scoped>
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
