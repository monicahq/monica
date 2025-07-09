<script setup>
import { ref, nextTick, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';
import draggable from 'vuedraggable';

const props = defineProps({
  data: Object,
});

const loadingState = ref(false);
const createEntryModalShown = ref(false);
const editEntryId = ref(0);
const localEntries = ref(props.data.quick_fact_templates);
const newEntry = useTemplateRef('newEntry');

const form = useForm({
  label: '',
  position: '',
  errors: [],
});

const showAddEntryModal = () => {
  form.label = '';
  form.position = '';
  createEntryModalShown.value = true;

  nextTick().then(() => newEntry.value.focus());
};

const renameEntryModal = (entry) => {
  form.label = entry.label;
  editEntryId.value = entry.id;

  nextTick().then(() => newEntry.value.focus());
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.quick_fact_templates_store, form)
    .then((response) => {
      localEntries.value.unshift(response.data.data);
      loadingState.value = null;
      createEntryModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const update = (entry) => {
  loadingState.value = 'loading';

  axios
    .put(entry.url.update, form)
    .then((response) => {
      localEntries.value[localEntries.value.findIndex((x) => x.id === entry.id)] = response.data.data;
      loadingState.value = null;
      editEntryId.value = 0;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const destroy = (entry) => {
  axios
    .delete(entry.url.destroy)
    .then(() => {
      var id = localEntries.value.findIndex((x) => x.id === entry.id);
      localEntries.value.splice(id, 1);
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const updatePosition = (event) => {
  // the event object comes from the draggable component
  form.position = event.moved.newIndex + 1;

  axios.put(event.moved.element.url.position, form).catch((error) => {
    loadingState.value = null;
    form.errors = error.response.data;
  });
};
</script>

<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="me-1"> 🧑‍🏭 </span>
        {{ $t('Quick facts template') }}
      </h3>
      <pretty-button
        v-if="!createEntryModalShown"
        :text="$t('Add an entry')"
        :icon="'plus'"
        @click="showAddEntryModal" />
    </div>

    <!-- modal to create a quick fact template entry -->
    <form
      v-if="createEntryModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <text-input
          ref="newEntry"
          v-model="form.label"
          :label="$t('Name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="createEntryModalShown = false" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createEntryModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- list of templates -->
    <div
      v-if="localEntries.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <draggable
        :list="localEntries"
        item-key="id"
        :component-data="{ name: 'fade' }"
        handle=".handle"
        @change="updatePosition">
        <template #item="{ element }">
          <div
            v-if="editEntryId !== element.id"
            class="item-list flex items-center justify-between border-b border-gray-200 py-2 pe-5 ps-4 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            <!-- icon to move position -->
            <div class="me-2 flex">
              <svg
                class="handle me-2 cursor-move"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M7 7H9V9H7V7Z" fill="currentColor" />
                <path d="M11 7H13V9H11V7Z" fill="currentColor" />
                <path d="M17 7H15V9H17V7Z" fill="currentColor" />
                <path d="M7 11H9V13H7V11Z" fill="currentColor" />
                <path d="M13 11H11V13H13V11Z" fill="currentColor" />
                <path d="M15 11H17V13H15V11Z" fill="currentColor" />
                <path d="M9 15H7V17H9V15Z" fill="currentColor" />
                <path d="M11 15H13V17H11V15Z" fill="currentColor" />
                <path d="M17 15H15V17H17V15Z" fill="currentColor" />
              </svg>

              <span>{{ element.label }}</span>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li class="inline cursor-pointer" @click="renameEntryModal(element)">
                <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
              </li>
              <li class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(element)">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>

          <form
            v-else
            class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
            @submit.prevent="update(element)">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <errors :errors="form.errors" />

              <text-input
                ref="newEntry"
                v-model="form.label"
                :label="$t('Name')"
                :type="'text'"
                :autofocus="true"
                :input-class="'block w-full'"
                :required="true"
                :autocomplete="false"
                :maxlength="255"
                @esc-key-pressed="editEntryId = 0" />
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="editEntryId = 0" />
              <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
            </div>
          </form>
        </template>
      </draggable>
    </div>

    <!-- blank state -->
    <div v-if="localEntries.length === 0">
      <p class="p-5 text-center">{{ $t('Quick facts let you document interesting facts about a contact.') }}</p>
    </div>
  </div>
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
