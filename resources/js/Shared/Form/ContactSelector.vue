<script setup>
import { computed, nextTick, onMounted, ref, watch, useTemplateRef } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import Errors from '@/Shared/Form/Errors.vue';
import { ScanSearch } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  inputClass: String,
  placeholder: {
    type: String,
    default: () => trans('Find a contact in this vault'),
  },
  label: String,
  labelCta: {
    type: String,
    default: () => trans('+ add a contact'),
  },
  type: {
    type: String,
    default: 'text',
  },
  required: Boolean,
  displayMostConsultedContacts: Boolean,
  addMultipleContacts: Boolean,
  searchUrl: String,
  mostConsultedContactsUrl: String,
});

const emit = defineEmits(['update:modelValue']);

const searchInput = useTemplateRef('searchInput');
const addContactMode = ref(false);
const processingSearch = ref(false);
const localContacts = ref(props.modelValue);
const mostConsultedContacts = ref([]);
const searchResults = ref([]);
const form = useForm({
  searchTerm: '',
  errors: [],
});

const displayAddContactButton = computed(() => {
  if (!props.addMultipleContacts && localContacts.value.length >= 1) {
    return false;
  }

  return !addContactMode.value;
});

const localInputClasses = computed(() => {
  return [
    'ps-8 w-full rounded-md shadow-xs',
    'bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-700',
    'focus:border-indigo-300 focus:ring-3 focus:ring-indigo-200/50',
    'disabled:bg-slate-50 dark:disabled:bg-slate-900',
    props.inputClass,
  ];
});

onMounted(() => {
  if (props.displayMostConsultedContacts) {
    lookupMostConsultedContacts();
  }
});

watch(
  () => props.modelValue,
  (value) => {
    localContacts.value = value;
  },
);

const showAddContactMode = () => {
  addContactMode.value = true;
  form.searchTerm = '';
  nextTick().then(() => searchInput.value.focus());
};

const sendEscKey = () => {
  search.cancel();
  addContactMode.value = false;
};

const lookupMostConsultedContacts = () => {
  axios.get(props.mostConsultedContactsUrl).then((response) => {
    mostConsultedContacts.value = response.data.data;
  });
};

const add = (contact) => {
  let id = localContacts.value.findIndex((x) => x.id === contact.id);

  if (id === -1) {
    localContacts.value.push(contact);
    form.searchTerm = '';
    addContactMode.value = false;
    nextTick().then(() => emit('update:modelValue', localContacts.value));
  }
};

const remove = (contact) => {
  let id = localContacts.value.findIndex((x) => x.id === contact.id);
  localContacts.value.splice(id, 1);

  nextTick().then(() => emit('update:modelValue', localContacts.value));
};

const search = _.debounce(() => {
  if (form.searchTerm !== '' && form.searchTerm.length >= 3) {
    processingSearch.value = true;

    axios
      .post(props.searchUrl, form)
      .then((response) => {
        searchResults.value = _.filter(response.data.data, (contact) =>
          _.every(localContacts.value, (e) => contact.id !== e.id),
        );
        processingSearch.value = false;
      })
      .catch((error) => {
        form.errors = error.response.data;
        processingSearch.value = false;
      });
  } else {
    searchResults.value = [];
  }
}, 300);
</script>

<template>
  <div>
    <!-- input -->
    <div>
      <label v-if="label" class="mb-2 block text-sm">
        {{ label }}
        <span v-if="!required" class="optional-badge rounded-xs px-[3px] py-px text-xs">
          {{ $t('optional') }}
        </span>
      </label>

      <!-- list of selected contacts -->
      <ul
        v-if="localContacts.length > 0"
        class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="contact in localContacts"
          :key="contact.id"
          class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <Link :href="contact.url">
            {{ contact.name }}
          </Link>

          <!-- actions -->
          <ul class="text-sm">
            <li class="inline cursor-pointer text-blue-500 hover:underline" @click="remove(contact)">
              {{ $t('Remove') }}
            </li>
          </ul>
        </li>
      </ul>

      <p
        v-if="displayAddContactButton"
        class="inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300 dark:border-gray-700 dark:bg-slate-500 dark:text-gray-900 dark:hover:bg-slate-700"
        @click="showAddContactMode">
        {{ labelCta }}
      </p>
    </div>

    <!-- mode to add a contact -->
    <div v-if="addContactMode">
      <div class="relative mb-3">
        <ScanSearch class="absolute start-2 top-2 h-4 w-4 text-gray-400" />

        <input
          ref="searchInput"
          v-model="form.searchTerm"
          :class="localInputClasses"
          :type="type"
          :name="name"
          :required="required"
          :placeholder="placeholder"
          @keyup="search"
          @keydown.esc="sendEscKey" />
        <span v-if="maxlength && displayMaxLength" class="length absolute rounded-xs text-xs">
          {{ charactersLeft }}
        </span>
      </div>

      <!-- blank state - case where we suggest most contacted contacts in the vault -->
      <div
        v-if="
          localContacts.length === 0 &&
          displayMostConsultedContacts &&
          searchResults.length === 0 &&
          form.searchTerm.length === 0
        "
        class="mb-6">
        <p class="mb-2 mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          {{ $t('Maybe one of these contacts?') }}
        </p>
        <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li
            v-for="contact in mostConsultedContacts"
            :key="contact.id"
            class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            {{ contact.name }}

            <!-- actions -->
            <ul class="text-sm">
              <li class="inline cursor-pointer text-blue-500 hover:underline" @click="add(contact)">{{ $t('Add') }}</li>
            </ul>
          </li>
        </ul>
      </div>

      <!-- searching results -->
      <div
        v-if="processingSearch"
        class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900">
        <p>{{ $t('Searching…') }}</p>
      </div>

      <!-- not enough characters -->
      <div
        v-if="form.searchTerm.length < 3 && form.searchTerm.length !== 0"
        class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900">
        <p>{{ $t('Please enter at least 3 characters to initiate a search.') }}</p>
      </div>

      <!-- search results: results found -->
      <div v-if="searchResults.length !== 0 && form.searchTerm.length !== 0" class="mb-3">
        <errors :errors="form.errors" />

        <ul class="mb-4 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <li
            v-for="contact in searchResults"
            :key="contact.id"
            class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            {{ contact.name }}

            <!-- actions -->
            <ul class="text-sm">
              <li class="inline cursor-pointer text-blue-500 hover:underline" @click="add(contact)">{{ $t('Add') }}</li>
            </ul>
          </li>
        </ul>
      </div>

      <!-- search results: no results found -->
      <div
        v-if="searchResults.length === 0 && form.searchTerm.length >= 3"
        class="mb-3 rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-900">
        <p>{{ $t('No results found') }}</p>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.optional-badge {
  color: #283e59;
  background-color: #edf2f9;
}

.dark .optional-badge {
  color: #d4d8dd !important;
  background-color: #2f3031 !important;
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
