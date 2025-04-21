<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  data: Object,
});

const form = useForm({
  show_group_tab: props.data.visibility.show_group_tab,
  show_tasks_tab: props.data.visibility.show_tasks_tab,
  show_files_tab: props.data.visibility.show_files_tab,
  show_journal_tab: props.data.visibility.show_journal_tab,
  show_companies_tab: props.data.visibility.show_companies_tab,
  show_reports_tab: props.data.visibility.show_reports_tab,
  show_calendar_tab: props.data.visibility.show_calendar_tab,
});

const update = () => {
  form.put(props.data.url.update_tab_visibility);
};
</script>

<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="me-1"> 🎟️ </span>

        {{ $t('Tabs visibility') }}
      </h3>
    </div>

    <div class="mb-6 rounded-xs border border-gray-200 text-sm dark:border-gray-700">
      <!-- help text -->
      <div class="flex rounded-t border-b border-gray-200 bg-slate-50 px-3 py-2 dark:border-gray-700 dark:bg-slate-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 pe-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <div class="inline">
          <p>{{ $t('You might need to reload the page to see the changes.') }}</p>
        </div>
      </div>
      <ul class="rounded-b rounded-t bg-white dark:bg-gray-900">
        <li
          class="item-list border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <label for="toggle-calendar" class="relative inline-flex cursor-pointer items-center">
            <input
              id="toggle-calendar"
              v-model="form.show_calendar_tab"
              type="checkbox"
              class="peer hidden"
              @change="update" />
            <div
              class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[22px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
            <span class="ms-3 dark:text-gray-300"> {{ $t('Show Calendar tab') }} </span>
          </label>
        </li>
        <li
          class="item-list border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <label for="toggle-journal" class="relative inline-flex cursor-pointer items-center">
            <input
              id="toggle-journal"
              v-model="form.show_journal_tab"
              type="checkbox"
              class="peer hidden"
              @change="update" />
            <div
              class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[22px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
            <span class="ms-3 dark:text-gray-300"> {{ $t('Show Journals tab') }} </span>
          </label>
        </li>
        <li
          class="item-list border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <label for="toggle-group" class="relative inline-flex cursor-pointer items-center">
            <input
              id="toggle-group"
              v-model="form.show_group_tab"
              type="checkbox"
              class="peer hidden"
              @change="update" />
            <div
              class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[22px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
            <span class="ms-3 dark:text-gray-300"> {{ $t('Show Groups tab') }} </span>
          </label>
        </li>
        <li
          class="item-list border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <label for="toggle-companies" class="relative inline-flex cursor-pointer items-center">
            <input
              id="toggle-companies"
              v-model="form.show_companies_tab"
              type="checkbox"
              class="peer hidden"
              @change="update" />
            <div
              class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[22px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
            <span class="ms-3 dark:text-gray-300"> {{ $t('Show Companies tab') }} </span>
          </label>
        </li>
        <li
          class="item-list border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <label for="toggle-tasks" class="relative inline-flex cursor-pointer items-center">
            <input
              id="toggle-tasks"
              v-model="form.show_tasks_tab"
              type="checkbox"
              class="peer hidden"
              @change="update" />
            <div
              class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[22px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
            <span class="ms-3 dark:text-gray-300"> {{ $t('Show Tasks tab') }} </span>
          </label>
        </li>
        <li
          class="item-list border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <label for="toggle-reports" class="relative inline-flex cursor-pointer items-center">
            <input
              id="toggle-reports"
              v-model="form.show_reports_tab"
              type="checkbox"
              class="peer hidden"
              @change="update" />
            <div
              class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[22px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
            <span class="ms-3 dark:text-gray-300"> {{ $t('Show Reports tab') }} </span>
          </label>
        </li>
        <li
          class="item-list border-b border-gray-200 p-3 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <label for="toggle-files" class="relative inline-flex cursor-pointer items-center">
            <input
              id="toggle-files"
              v-model="form.show_files_tab"
              type="checkbox"
              class="peer hidden"
              @change="update" />
            <div
              class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:right-[22px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-800 dark:peer-focus:ring-blue-800" />
            <span class="ms-3 dark:text-gray-300"> {{ $t('Show Files tab') }} </span>
          </label>
        </li>
      </ul>
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
