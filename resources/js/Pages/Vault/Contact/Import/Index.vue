<!--
  vCard Import Feature - Import Interface Component

  @author Clément ABRAHAM <https://github.com/cabraham2>
  @version 1.0.0
  @created 2026-01-08
  @updated 2026-01-08
  @license MIT

  Interactive Vue component for vCard import workflow:
  - Drag & drop file upload
  - Contact preview with search and sort
  - Multi-select with checkboxes
  - Progress tracking and result summary
-->

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import axios from 'axios';
import Layout from '@/Layouts/Layout.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import Errors from '@/Shared/Form/Errors.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

// States
const step = ref(1); // 1: upload, 2: preview, 3: importing, 4: complete
const contacts = ref([]);
const selectedIndices = ref(new Set());
const sessionKey = ref(null);
const sortBy = ref('name'); // name, first_name, last_name
const sortOrder = ref('asc');
const searchTerm = ref('');
const isUploading = ref(false);
const uploadProgress = ref(0);
const isImporting = ref(false);
const importProgress = ref(0);
const importResults = ref(null);
const errors = ref([]);
const selectedContact = ref(null);
const showContactModal = ref(false);

// Drag and drop
const isDragging = ref(false);
const fileInput = ref(null);

// Filter and sort contacts
const filteredContacts = computed(() => {
  // Exclude contacts with errors first
  let filtered = contacts.value.filter((contact) => !contact.error);

  // Search filter
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase();
    filtered = filtered.filter((contact) => {
      return (
        (contact.name && contact.name.toLowerCase().includes(term)) ||
        (contact.email && contact.email.toLowerCase().includes(term)) ||
        (contact.phone && contact.phone.toLowerCase().includes(term)) ||
        (contact.organization && contact.organization.toLowerCase().includes(term))
      );
    });
  }

  // Sort
  filtered = [...filtered].sort((a, b) => {
    let aValue, bValue;

    switch (sortBy.value) {
      case 'first_name':
        aValue = (a.first_name || '').toLowerCase();
        bValue = (b.first_name || '').toLowerCase();
        break;
      case 'last_name':
        aValue = (a.last_name || '').toLowerCase();
        bValue = (b.last_name || '').toLowerCase();
        break;
      case 'email':
        aValue = (a.email || '').toLowerCase();
        bValue = (b.email || '').toLowerCase();
        break;
      case 'phone':
        aValue = (a.phone || '').toLowerCase();
        bValue = (b.phone || '').toLowerCase();
        break;
      case 'organization':
        aValue = (a.organization || '').toLowerCase();
        bValue = (b.organization || '').toLowerCase();
        break;
      case 'name':
      default:
        aValue = (a.name || '').toLowerCase();
        bValue = (b.name || '').toLowerCase();
        break;
    }

    if (aValue === bValue) return 0;
    const comparison = aValue.localeCompare(bValue);
    return sortOrder.value === 'asc' ? comparison : -comparison;
  });

  return filtered;
});

const selectedCount = computed(() => selectedIndices.value.size);
const totalCount = computed(() => contacts.value.filter((c) => !c.error).length);
const allSelected = computed(() => selectedCount.value === totalCount.value);

// Methods
const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    uploadFile(file);
  }
};

const handleDrop = (event) => {
  event.preventDefault();
  isDragging.value = false;

  const file = event.dataTransfer.files[0];
  if (file) {
    uploadFile(file);
  }
};

const handleDragOver = (event) => {
  event.preventDefault();
  isDragging.value = true;
};

const handleDragLeave = () => {
  isDragging.value = false;
};

const triggerFileInput = () => {
  fileInput.value.click();
};

const uploadFile = async (file) => {
  errors.value = [];
  isUploading.value = true;
  uploadProgress.value = 0;

  const formData = new FormData();
  formData.append('file', file);

  try {
    const response = await axios.post(props.data.url.upload, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
      },
    });

    contacts.value = response.data.data.contacts;
    sessionKey.value = response.data.data.session_key;

    // Select all valid contacts by default
    selectedIndices.value = new Set(
      contacts.value.filter((c) => !c.error).map((c) => c.index)
    );

    step.value = 2;
  } catch (error) {
    errors.value = [error.response?.data?.message || trans('Failed to upload file')];
  } finally {
    isUploading.value = false;
    uploadProgress.value = 0;
  }
};

const toggleSelect = (index) => {
  if (selectedIndices.value.has(index)) {
    selectedIndices.value.delete(index);
  } else {
    selectedIndices.value.add(index);
  }
  selectedIndices.value = new Set(selectedIndices.value); // Trigger reactivity
};

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedIndices.value.clear();
  } else {
    selectedIndices.value = new Set(contacts.value.filter((c) => !c.error).map((c) => c.index));
  }
  selectedIndices.value = new Set(selectedIndices.value); // Trigger reactivity
};

const changeSortBy = (field) => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = field;
    sortOrder.value = 'asc';
  }
};

const startImport = async () => {
  if (selectedIndices.value.size === 0) {
    errors.value = [trans('Please select at least one contact to import')];
    return;
  }

  isImporting.value = true;
  step.value = 3;
  importProgress.value = 0;

  const totalSelected = selectedIndices.value.size;
  const chunkSize = 50; // Import 50 contacts per request
  let offset = 0;
  let allImported = [];
  let allErrors = [];

  try {
    while (offset < totalSelected) {
      const response = await axios.post(props.data.url.import, {
        session_key: sessionKey.value,
        selected_indices: Array.from(selectedIndices.value),
        chunk_size: chunkSize,
        offset: offset,
      });

      const data = response.data.data;
      
      // Accumulate results
      allImported = allImported.concat(data.imported);
      allErrors = allErrors.concat(data.errors);
      
      // Update progress
      importProgress.value = data.progress;
      
      // Check if completed
      if (data.completed) {
        importResults.value = {
          imported: allImported,
          errors: allErrors,
          success_count: allImported.length,
          error_count: allErrors.length,
          redirect: data.redirect,
        };
        setTimeout(() => {
          step.value = 4;
        }, 500);
        break;
      }
      
      // Move to next chunk
      offset = data.next_offset;
    }
  } catch (error) {
    errors.value = [error.response?.data?.message || trans('Import failed')];
    step.value = 2;
  } finally {
    isImporting.value = false;
  }
};

const finishImport = () => {
  if (importResults.value && importResults.value.redirect) {
    router.visit(importResults.value.redirect);
  } else {
    router.visit(props.data.url.back);
  }
};

const cancelImport = async () => {
  try {
    await axios.delete(props.data.url.cancel);
  } catch (error) {
    // Ignore errors on cancel
  }
  router.visit(props.data.url.back);
};

const startOver = () => {
  step.value = 1;
  contacts.value = [];
  uploadProgress.value = 0;
};

const viewContactDetails = (contact) => {
  selectedContact.value = contact;
  showContactModal.value = true;
};

const closeContactModal = () => {
  showContactModal.value = false;
  selectedContact.value = null;
};
</script>

<template>
  <Layout :title="$t('Import contacts')" :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <InertiaLink :href="data.url.back" class="text-blue-500 hover:underline">
                {{ $t('Contacts') }}
              </InertiaLink>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="me-2 inline">
              {{ $t('Import contacts') }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-10">
      <div class="mx-auto max-w-5xl px-2 py-6 sm:px-6 lg:px-8">
        <!-- Errors -->
        <Errors v-if="errors.length > 0" :errors="errors" class="mb-6" />

        <!-- Step 1: Upload -->
        <div v-if="step === 1" class="rounded-lg bg-white p-8 shadow dark:bg-gray-900">
          <div class="mb-6 text-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
              {{ $t('Import contacts from vCard') }}
            </h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
              {{ $t('Upload a .vcf or .vcard file to import your contacts') }}
            </p>
          </div>

          <!-- Drag and drop area -->
          <div
            class="relative border-2 border-dashed rounded-lg p-12 text-center transition-all duration-200"
            :class="{
              'border-blue-500 bg-blue-50 dark:bg-blue-900/20': isDragging,
              'border-gray-300 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-600':
                !isDragging,
            }"
            @drop="handleDrop"
            @dragover="handleDragOver"
            @dragleave="handleDragLeave">
            <input
              ref="fileInput"
              type="file"
              class="hidden"
              accept=".vcf,.vcard"
              @change="handleFileSelect" />

            <div v-if="!isUploading">
              <svg
                class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>

              <p class="mt-4 text-lg text-gray-700 dark:text-gray-300">
                {{ $t('Drag and drop your vCard file here') }}
              </p>
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">
                {{ $t('or') }}
              </p>
              <button
                type="button"
                class="mt-4 rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700 transition-colors duration-200"
                @click="triggerFileInput">
                {{ $t('Select a file') }}
              </button>

              <p class="mt-4 text-xs text-gray-500 dark:text-gray-500">
                {{ $t('Supported formats: .vcf, .vcard (max 50MB)') }}
              </p>
            </div>

            <div v-else class="flex flex-col items-center">
              <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600"></div>
              <p class="mt-4 text-gray-700 dark:text-gray-300">
                {{ $t('Uploading and analyzing...') }}
              </p>
              <div class="mt-4 w-64">
                <div class="h-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-blue-600 transition-all duration-300"
                    :style="{ width: uploadProgress + '%' }"></div>
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">
                  {{ uploadProgress }}%
                </p>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-center">
            <pretty-link :href="data.url.back" :text="$t('Cancel')" class="text-gray-600" />
          </div>
        </div>

        <!-- Step 2: Preview and selection -->
        <div v-if="step === 2" class="rounded-lg bg-white shadow dark:bg-gray-900">
          <!-- Header -->
          <div class="border-b border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
              {{ $t('Select contacts to import') }}
            </h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
              {{
                $t(':count contacts found. Select the ones you want to import.', {
                  count: totalCount,
                })
              }}
            </p>
          </div>

          <!-- Toolbar -->
          <div class="border-b border-gray-200 dark:border-gray-700 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <!-- Search -->
              <div class="flex-1 min-w-[200px]">
                <input
                  v-model="searchTerm"
                  type="text"
                  class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                  :placeholder="$t('Search contacts...')" />
              </div>

              <!-- Sort controls -->
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $t('Sort by:') }}</span>
                <button
                  class="px-3 py-1 rounded text-sm transition-colors"
                  :class="{
                    'bg-blue-600 text-white': sortBy === 'name',
                    'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600':
                      sortBy !== 'name',
                  }"
                  @click="changeSortBy('name')">
                  {{ $t('Name') }}
                  <span v-if="sortBy === 'name'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                </button>
                <button
                  class="px-3 py-1 rounded text-sm transition-colors"
                  :class="{
                    'bg-blue-600 text-white': sortBy === 'first_name',
                    'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600':
                      sortBy !== 'first_name',
                  }"
                  @click="changeSortBy('first_name')">
                  {{ $t('First name') }}
                  <span v-if="sortBy === 'first_name'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                </button>
                <button
                  class="px-3 py-1 rounded text-sm transition-colors"
                  :class="{
                    'bg-blue-600 text-white': sortBy === 'last_name',
                    'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600':
                      sortBy !== 'last_name',
                  }"
                  @click="changeSortBy('last_name')">
                  {{ $t('Last name') }}
                  <span v-if="sortBy === 'last_name'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                </button>
              </div>

              <!-- Select all -->
              <div class="flex items-center gap-2">
                <label class="flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    :checked="allSelected"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
                    @change="toggleSelectAll" />
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    {{ $t('Select all (:count)', { count: totalCount }) }}
                  </span>
                </label>
              </div>
            </div>

            <!-- Selection count -->
            <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
              {{ $t(':selected of :total contacts selected', { selected: selectedCount, total: totalCount }) }}
            </div>
          </div>

          <!-- Contacts list -->
          <div class="max-h-[500px] overflow-y-auto">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                <tr>
                  <th class="w-12 px-4 py-3"></th>
                  <th class="px-4 py-3 text-left">
                    <button
                      type="button"
                      class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center gap-1"
                      @click="changeSortBy('name')">
                      {{ $t('Name') }}
                      <span v-if="sortBy === 'name'" class="text-blue-600 dark:text-blue-400">
                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                      </span>
                    </button>
                  </th>
                  <th class="px-4 py-3 text-left">
                    <button
                      type="button"
                      class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center gap-1"
                      @click="changeSortBy('email')">
                      {{ $t('Email') }}
                      <span v-if="sortBy === 'email'" class="text-blue-600 dark:text-blue-400">
                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                      </span>
                    </button>
                  </th>
                  <th class="px-4 py-3 text-left">
                    <button
                      type="button"
                      class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center gap-1"
                      @click="changeSortBy('phone')">
                      {{ $t('Phone') }}
                      <span v-if="sortBy === 'phone'" class="text-blue-600 dark:text-blue-400">
                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                      </span>
                    </button>
                  </th>
                  <th class="px-4 py-3 text-left">
                    <button
                      type="button"
                      class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center gap-1"
                      @click="changeSortBy('organization')">
                      {{ $t('Organization') }}
                      <span v-if="sortBy === 'organization'" class="text-blue-600 dark:text-blue-400">
                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                      </span>
                    </button>
                  </th>
                  <th class="w-24 px-4 py-3 text-center text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('Data') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                  v-for="contact in filteredContacts"
                  :key="contact.index"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150"
                  :class="{
                    'bg-red-50 dark:bg-red-900/20': contact.error,
                    'opacity-50': contact.error,
                  }">
                  <td class="px-4 py-3">
                    <input
                      v-if="!contact.error"
                      type="checkbox"
                      :checked="selectedIndices.has(contact.index)"
                      class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
                      @change="toggleSelect(contact.index)" />
                  </td>
                  <td class="px-4 py-3">
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                      {{ contact.name }}
                    </div>
                    <div v-if="contact.nickname" class="text-xs text-gray-500 dark:text-gray-500">
                      "{{ contact.nickname }}"
                    </div>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <div v-if="contact.email">
                      {{ contact.email }}
                      <span v-if="contact.emails_count > 1" class="text-xs text-gray-500">
                        (+{{ contact.emails_count - 1 }})
                      </span>
                    </div>
                    <span v-else class="text-gray-400 dark:text-gray-600">—</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    <div v-if="contact.phone">
                      {{ contact.phone }}
                      <span v-if="contact.phones_count > 1" class="text-xs text-gray-500">
                        (+{{ contact.phones_count - 1 }})
                      </span>
                    </div>
                    <span v-else class="text-gray-400 dark:text-gray-600">—</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    {{ contact.organization || '—' }}
                  </td>
                  <td class="px-4 py-3 text-center">
                    <button
                      type="button"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all"
                      :class="{
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400':
                          contact.data_points > 2,
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400':
                          contact.data_points <= 2 && contact.data_points > 0,
                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400':
                          contact.data_points === 0,
                      }"
                      @click="viewContactDetails(contact)">
                      {{ contact.data_points }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Empty state -->
            <div v-if="filteredContacts.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-500">
              {{ $t('No contacts found matching your search') }}
            </div>
          </div>

          <!-- Footer actions -->
          <div class="border-t border-gray-200 dark:border-gray-700 p-6 flex justify-between items-center">
            <button
              type="button"
              class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
              @click="cancelImport">
              {{ $t('Cancel') }}
            </button>

            <button
              type="button"
              class="rounded-lg bg-blue-600 px-8 py-3 font-medium text-white hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="selectedCount === 0"
              @click="startImport">
              {{
                selectedCount > 0
                  ? $t('Import :count selected contact(s)', { count: selectedCount })
                  : $t('Select contacts to import')
              }}
            </button>
          </div>
        </div>

        <!-- Step 3: Importing -->
        <div v-if="step === 3" class="rounded-lg bg-white p-12 shadow dark:bg-gray-900 text-center">
          <div class="mx-auto max-w-md">
            <div class="mb-6">
              <svg
                class="mx-auto h-20 w-20 text-blue-600 animate-spin"
                fill="none"
                viewBox="0 0 24 24">
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
              {{ $t('Importing contacts...') }}
            </h2>

            <p class="text-gray-600 dark:text-gray-400 mb-6">
              {{ $t('Please wait while we import your contacts') }}
            </p>

            <!-- Progress bar -->
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 mb-4">
              <div
                class="bg-blue-600 h-4 rounded-full transition-all duration-300"
                :style="{ width: importProgress + '%' }"></div>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ importProgress }}%
            </p>
          </div>
        </div>

        <!-- Step 4: Complete -->
        <div v-if="step === 4" class="rounded-lg bg-white p-12 shadow dark:bg-gray-900 text-center">
          <div class="mx-auto max-w-md">
            <div class="mb-6">
              <svg
                class="mx-auto h-20 w-20 text-green-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
              {{ $t('Import complete!') }}
            </h2>

            <div v-if="importResults" class="mb-6">
              <p class="text-lg text-gray-700 dark:text-gray-300 mb-2">
                {{
                  $t(':count contact(s) imported successfully', {
                    count: importResults.success_count,
                  })
                }}
              </p>

              <div v-if="importResults.error_count > 0" class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-400">
                  {{
                    $t(':count contact(s) could not be imported', {
                      count: importResults.error_count,
                    })
                  }}
                </p>
                
                <!-- Liste des contacts en erreur -->
                <div v-if="importResults.errors && importResults.errors.length > 0" class="mt-3 space-y-2">
                  <details class="cursor-pointer">
                    <summary class="text-xs font-medium text-yellow-700 dark:text-yellow-300 hover:text-yellow-900 dark:hover:text-yellow-100">
                      {{ $t('Show error details') }}
                    </summary>
                    <div class="mt-2 max-h-60 overflow-y-auto bg-white dark:bg-gray-800 rounded border border-yellow-200 dark:border-yellow-700 p-3">
                      <div
                        v-for="(error, idx) in importResults.errors"
                        :key="idx"
                        class="text-xs py-2 border-b border-gray-200 dark:border-gray-700 last:border-0">
                        <span class="font-mono text-gray-600 dark:text-gray-400">#{{ error.index }}</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100 ml-2">{{ error.contact_name }}</span>
                        <div class="text-gray-500 dark:text-gray-400 mt-1">
                          {{ error.message || error.error_type }}
                        </div>
                      </div>
                    </div>
                  </details>
                </div>
              </div>
            </div>

            <div class="flex justify-center gap-4">
              <button
                type="button"
                class="rounded-lg bg-gray-200 dark:bg-gray-700 px-6 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200"
                @click="startOver">
                {{ $t('Import more contacts') }}
              </button>

              <button
                type="button"
                class="rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700 transition-colors duration-200"
                @click="finishImport">
                {{ $t('View contacts') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Contact Details Modal -->
    <div
      v-if="showContactModal && selectedContact"
      class="fixed inset-0 z-50 overflow-y-auto"
      @click.self="closeContactModal">
      <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeContactModal"></div>

        <!-- Modal -->
        <div class="relative bg-white dark:bg-gray-900 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
              {{ selectedContact.name }}
            </h3>
            <button
              type="button"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              @click="closeContactModal">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="overflow-y-auto max-h-[60vh] p-6">
            <!-- Contact details -->
            <div class="space-y-6">
              <!-- Photo & Basic Info Card -->
              <div class="flex gap-6">
                <!-- Photo -->
                <div v-if="selectedContact.photo_url" class="flex-shrink-0">
                  <img
                    :src="selectedContact.photo_url"
                    alt="Contact photo"
                    class="w-32 h-32 rounded-lg object-cover shadow-md" />
                </div>

                <!-- Basic Info -->
                <div class="flex-1">
                  <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ $t('Basic Information') }}</h4>
                  <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div v-if="selectedContact.prefix">
                      <dt class="text-gray-600 dark:text-gray-400">{{ $t('Prefix') }}</dt>
                      <dd class="text-gray-900 dark:text-gray-100">{{ selectedContact.prefix }}</dd>
                    </div>
                    <div v-if="selectedContact.first_name">
                      <dt class="text-gray-600 dark:text-gray-400">{{ $t('First name') }}</dt>
                      <dd class="text-gray-900 dark:text-gray-100">{{ selectedContact.first_name }}</dd>
                    </div>
                    <div v-if="selectedContact.middle_name">
                      <dt class="text-gray-600 dark:text-gray-400">{{ $t('Middle name') }}</dt>
                      <dd class="text-gray-900 dark:text-gray-100">{{ selectedContact.middle_name }}</dd>
                    </div>
                    <div v-if="selectedContact.last_name">
                      <dt class="text-gray-600 dark:text-gray-400">{{ $t('Last name') }}</dt>
                      <dd class="text-gray-900 dark:text-gray-100">{{ selectedContact.last_name }}</dd>
                    </div>
                    <div v-if="selectedContact.suffix">
                      <dt class="text-gray-600 dark:text-gray-400">{{ $t('Suffix') }}</dt>
                      <dd class="text-gray-900 dark:text-gray-100">{{ selectedContact.suffix }}</dd>
                    </div>
                    <div v-if="selectedContact.nickname">
                      <dt class="text-gray-600 dark:text-gray-400">{{ $t('Nickname') }}</dt>
                      <dd class="text-gray-900 dark:text-gray-100">"{{ selectedContact.nickname }}"</dd>
                    </div>
                  </dl>
                </div>
              </div>

              <!-- Emails -->
              <div v-if="selectedContact.emails && selectedContact.emails.length > 0">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  {{ $t('Emails') }} ({{ selectedContact.emails.length }})
                </h4>
                <div class="space-y-1">
                  <div v-for="(email, index) in selectedContact.emails" :key="index" class="text-sm">
                    <span class="text-gray-900 dark:text-gray-100">{{ email.value }}</span>
                    <span v-if="email.type" class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ email.type }})</span>
                  </div>
                </div>
              </div>

              <!-- Phones -->
              <div v-if="selectedContact.phones && selectedContact.phones.length > 0">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  {{ $t('Phones') }} ({{ selectedContact.phones.length }})
                </h4>
                <div class="space-y-1">
                  <div v-for="(phone, index) in selectedContact.phones" :key="index" class="text-sm">
                    <span class="text-gray-900 dark:text-gray-100">{{ phone.value }}</span>
                    <span v-if="phone.type" class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ phone.type }})</span>
                  </div>
                </div>
              </div>

              <!-- Work / Organization & Title -->
              <div v-if="selectedContact.organization || selectedContact.title">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ $t('Work') }}</h4>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                  <div v-if="selectedContact.organization">
                    {{ selectedContact.organization.replace(/;+$/g, '') }}
                  </div>
                  <div v-if="selectedContact.title" class="text-gray-600 dark:text-gray-400">
                    {{ selectedContact.title }}
                  </div>
                </div>
              </div>

              <!-- Addresses -->
              <div v-if="selectedContact.addresses && selectedContact.addresses.length > 0">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  {{ $t('Addresses') }} ({{ selectedContact.addresses.length }})
                </h4>
                <div class="space-y-3">
                  <div v-for="(address, index) in selectedContact.addresses" :key="index" class="text-sm">
                    <div v-if="address.type" class="text-xs text-gray-500 dark:text-gray-400 mb-1">({{ address.type }})</div>
                    <div class="text-gray-900 dark:text-gray-100">
                      <div v-if="address.street">{{ address.street }}</div>
                      <div>
                        <span v-if="address.city">{{ address.city }}</span>
                        <span v-if="address.region">, {{ address.region }}</span>
                        <span v-if="address.postal_code"> {{ address.postal_code }}</span>
                      </div>
                      <div v-if="address.country">{{ address.country }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- URLs -->
              <div v-if="selectedContact.urls && selectedContact.urls.length > 0">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  {{ $t('Websites') }} ({{ selectedContact.urls.length }})
                </h4>
                <div class="space-y-1">
                  <div v-for="(url, index) in selectedContact.urls" :key="index" class="text-sm">
                    <a :href="url" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                      {{ url }}
                    </a>
                  </div>
                </div>
              </div>

              <!-- Birthday -->
              <div v-if="selectedContact.birthday">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ $t('Birthday') }}</h4>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                  {{ selectedContact.birthday }}
                </div>
              </div>

              <!-- Note -->
              <div v-if="selectedContact.note">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ $t('Note') }}</h4>
                <div class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                  {{ selectedContact.note }}
                </div>
              </div>

              <!-- Categories/Tags -->
              <div v-if="selectedContact.categories && selectedContact.categories.length > 0">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ $t('Categories') }}</h4>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="(category, index) in selectedContact.categories"
                    :key="index"
                    class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ category }}
                  </span>
                </div>
              </div>

              <!-- Data Points Summary -->
              <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-400">{{ $t('Total data points') }}</span>
                  <span class="font-semibold text-gray-900 dark:text-gray-100">{{ selectedContact.data_points }}</span>
                </div>
              </div>

              <!-- Error -->
              <div v-if="selectedContact.error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-sm text-red-800 dark:text-red-400">
                  <strong>{{ $t('Error') }}:</strong> {{ selectedContact.error_message }}
                </p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="border-t border-gray-200 dark:border-gray-700 p-6 flex justify-end">
            <button
              type="button"
              class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 transition-colors duration-200"
              @click="closeContactModal">
              {{ $t('Close') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}
</style>
