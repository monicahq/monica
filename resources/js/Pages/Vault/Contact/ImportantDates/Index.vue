<script setup>
import { ref, nextTick, useTemplateRef } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { flash } from '@/methods.js';
import Layout from '@/Layouts/Layout.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import CreateOrEditImportantDate from './Partials/CreateOrEditImportantDate.vue';
import Errors from '@/Shared/Form/Errors.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const editedDateId = ref(0);
const createDateModalShown = ref(false);
const localDates = ref(props.data.dates);
const createForm = useTemplateRef('createForm');
const editForm = useTemplateRef('editForm');
const errors = ref(null);

const showCreateModal = () => {
  createDateModalShown.value = true;

  nextTick().then(() => createForm.value.reset());
};

const updateDateModal = (date) => {
  editedDateId.value = date.id;

  nextTick().then(() => editForm.value[0].reset());
};

const created = (date) => {
  flash(trans('The date has been added'), 'success');
  localDates.value.unshift(date);
  createDateModalShown.value = false;
};

const updated = (date) => {
  flash(trans('The date has been updated'), 'success');
  localDates.value[localDates.value.findIndex((x) => x.id === date.id)] = date;
  editedDateId.value = 0;
};

const destroy = (date) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(date.url.destroy)
      .then(() => {
        flash(trans('The date has been deleted'), 'success');
        let id = localDates.value.findIndex((x) => x.id === date.id);
        localDates.value.splice(id, 1);
      })
      .catch((error) => {
        errors.value = error.response.data;
      });
  }
};
</script>

<template>
  <Layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
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
              <Link :href="data.url.contact" class="text-blue-500 hover:underline">
                {{ $t('Profile of :name', { name: data.contact.name }) }}
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
            <li class="inline">{{ $t('All the important dates') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0">
            <span class="me-1"> 🗓 </span>
            {{ $t('All the important dates') }}
          </h3>
          <PrettyButton v-if="!createDateModalShown" :text="$t('Add a date')" :icon="'plus'" @click="showCreateModal" />
        </div>

        <Errors :errors="errors" />

        <!-- modal to create a new date -->
        <CreateOrEditImportantDate
          v-if="createDateModalShown"
          class="mb-6"
          ref="createForm"
          :data="data"
          @close="createDateModalShown = false"
          @created="created" />

        <div v-if="!createDateModalShown">
          <!-- list of dates -->
          <ul
            v-if="localDates.length > 0"
            class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="date in localDates"
              :key="date.id"
              class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <!-- detail of the important date -->
              <div v-if="editedDateId === 0" class="flex items-center justify-between px-5 py-2">
                <span class="text-base">
                  {{ date.label }}: <span class="font-medium">{{ date.date }}</span>

                  <span
                    v-if="date.type"
                    class="ms-2 inline-block rounded-xs bg-neutral-200 px-1 py-0 text-xs text-neutral-500 last:me-0">
                    {{ date.type.label }}
                  </span>
                </span>

                <!-- actions -->
                <ul class="text-sm">
                  <li class="me-4 inline cursor-pointer" @click="updateDateModal(date)">
                    <span class="text-blue-500 hover:underline">{{ $t('Edit') }}</span>
                  </li>
                  <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(date)">
                    {{ $t('Delete') }}
                  </li>
                </ul>
              </div>

              <!-- edit date modal -->
              <CreateOrEditImportantDate
                ref="editForm"
                :date="date"
                v-if="editedDateId === date.id"
                :data="data"
                @close="editedDateId = 0"
                @update:date="updated" />
            </li>
          </ul>

          <!-- blank state -->
          <div v-else class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <p class="p-5 text-center">
              {{
                $t(
                  'Add an important date to remember what matters to you about this person, like a birthdate or a deceased date.',
                )
              }}
            </p>
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

.ant-calendar-picker {
  -tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --tw-shadow-colored: 0 1px 2px 0 var(--tw-shadow-color);
  box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
  --tw-border-opacity: 1;
  border-color: rgb(209 213 219 / var(--tw-border-opacity));
  border-radius: 0.375rem;
  padding-top: 0.5rem;
  padding-right: 0.75rem;
  padding-bottom: 0.5rem;
  padding-left: 0.75rem;
  font-size: 1rem;
  line-height: 1.5rem;
  border-width: 1px;
  appearance: none;
  background-color: #fff;
}
</style>
