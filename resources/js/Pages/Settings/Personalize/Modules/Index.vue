<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <InertiaLink :href="data.url.settings" class="text-blue-500 hover:underline">
                {{ $t('Settings') }}
              </InertiaLink>
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
              <InertiaLink :href="data.url.personalize" class="text-blue-500 hover:underline">
                {{ $t('Personalize your account') }}
              </InertiaLink>
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
            <li class="inline">Modules</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-16">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 sm:mt-0">
          <h3 class="mb-4 text-center text-xl sm:mb-2">All the modules in the account</h3>
        </div>

        <!-- help text -->
        <div class="mb-10 flex rounded-xs border bg-slate-50 px-3 py-2 text-sm dark:bg-slate-900">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 pe-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>

          <div>
            <p class="mb-1">Modules contain each one of your contact's data.</p>
            <p class="mb-1">
              Monica comes with a set of predefined modules that can't be edited or deleted – because we need them for
              Monica to function properly. However, you can create your own modules to record the data you want in your
              account.
            </p>
          </div>
        </div>

        <div class="grid-skeleton grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-0">
            <div class="mb-4 mt-8 items-center justify-between border-b pb-3 sm:mt-0 sm:flex">
              <h3>Modules</h3>
              <pretty-button :text="'Add new module'" :icon="'plus'" @click="showPageModal" />
            </div>

            <div>
              <!-- search a module -->
              <div
                class="module-list rounded-t-md border-x border-t border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                <text-input
                  v-model="form.search"
                  :type="'text'"
                  :autofocus="true"
                  :input-class="'block w-full'"
                  :required="true"
                  :autocomplete="false"
                  :placeholder="$t('Filter')"
                  :maxlength="255" />
              </div>

              <!-- list of modules -->
              <ul
                class="h-80 overflow-auto rounded-b border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <li
                  v-for="module in data.modules"
                  :key="module.id"
                  class="module-list border-b border-gray-200 px-5 py-2 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                  <span>{{ module.name }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- middle -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700">
            <h3 class="border-b border-gray-200 px-5 py-2 dark:border-gray-700">Module details</h3>

            <errors :errors="form.errors" />

            <!-- module details -->
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <text-input
                v-model="form.search"
                :type="'text'"
                :autofocus="true"
                :label="'Name of the module'"
                :input-class="'block w-full'"
                :required="true"
                :autocomplete="false"
                :maxlength="255" />
            </div>

            <!-- content of the module -->
            <div class="border-b border-gray-200 bg-gray-100 p-5 dark:border-gray-700">
              <div
                class="mb-2 rounded-xs border border-gray-300 bg-white px-5 py-3 text-center dark:bg-gray-900"
                @click="addRow()">
                + Add row
              </div>

              <div v-for="row in form.rows" :key="row.realId" class="mb-2">
                <div class="rounded-xs border border-gray-300 bg-white dark:bg-gray-900">
                  <!-- row options -->
                  <div class="flex justify-between border-b border-gray-200 px-3 py-1 text-xs dark:border-gray-700">
                    <div>
                      <div class="relative me-3 inline cursor-pointer">
                        <svg
                          class="me-1 inline h-3 w-3"
                          viewBox="0 0 24 24"
                          fill="none"
                          xmlns="http://www.w3.org/2000/svg">
                          <path
                            d="M12 6C12.5523 6 13 6.44772 13 7V11H17C17.5523 11 18 11.4477 18 12C18 12.5523 17.5523 13 17 13H13V17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17V13H7C6.44772 13 6 12.5523 6 12C6 11.4477 6.44772 11 7 11H11V7C11 6.44772 11.4477 6 12 6Z"
                            fill="currentColor" />
                          <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M5 22C3.34315 22 2 20.6569 2 19V5C2 3.34315 3.34315 2 5 2H19C20.6569 2 22 3.34315 22 5V19C22 20.6569 20.6569 22 19 22H5ZM4 19C4 19.5523 4.44772 20 5 20H19C19.5523 20 20 19.5523 20 19V5C20 4.44772 19.5523 4 19 4H5C4.44772 4 4 4.44772 4 5V19Z"
                            fill="currentColor" />
                        </svg>
                        <span>Add a field to the left</span>
                      </div>

                      <div class="relative me-2 inline cursor-pointer" @click="addFieldToRight(row)">
                        <svg
                          class="me-1 inline h-3 w-3"
                          viewBox="0 0 24 24"
                          fill="none"
                          xmlns="http://www.w3.org/2000/svg">
                          <path
                            d="M12 6C12.5523 6 13 6.44772 13 7V11H17C17.5523 11 18 11.4477 18 12C18 12.5523 17.5523 13 17 13H13V17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17V13H7C6.44772 13 6 12.5523 6 12C6 11.4477 6.44772 11 7 11H11V7C11 6.44772 11.4477 6 12 6Z"
                            fill="currentColor" />
                          <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M5 22C3.34315 22 2 20.6569 2 19V5C2 3.34315 3.34315 2 5 2H19C20.6569 2 22 3.34315 22 5V19C22 20.6569 20.6569 22 19 22H5ZM4 19C4 19.5523 4.44772 20 5 20H19C19.5523 20 20 19.5523 20 19V5C20 4.44772 19.5523 4 19 4H5C4.44772 4 4 4.44772 4 5V19Z"
                            fill="currentColor" />
                        </svg>
                        <span>Add a field to the right</span>
                      </div>
                    </div>

                    <div class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroyRow(row)">
                      <svg
                        class="me-1 inline h-3 w-3"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M16.3956 7.75734C16.7862 8.14786 16.7862 8.78103 16.3956 9.17155L13.4142 12.153L16.0896 14.8284C16.4802 15.2189 16.4802 15.8521 16.0896 16.2426C15.6991 16.6331 15.0659 16.6331 14.6754 16.2426L12 13.5672L9.32458 16.2426C8.93405 16.6331 8.30089 16.6331 7.91036 16.2426C7.51984 15.8521 7.51984 15.2189 7.91036 14.8284L10.5858 12.153L7.60436 9.17155C7.21383 8.78103 7.21383 8.14786 7.60436 7.75734C7.99488 7.36681 8.62805 7.36681 9.01857 7.75734L12 10.7388L14.9814 7.75734C15.372 7.36681 16.0051 7.36681 16.3956 7.75734Z"
                          fill="currentColor" />
                        <path
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M4 1C2.34315 1 1 2.34315 1 4V20C1 21.6569 2.34315 23 4 23H20C21.6569 23 23 21.6569 23 20V4C23 2.34315 21.6569 1 20 1H4ZM20 3H4C3.44772 3 3 3.44772 3 4V20C3 20.5523 3.44772 21 4 21H20C20.5523 21 21 20.5523 21 20V4C21 3.44772 20.5523 3 20 3Z"
                          fill="currentColor" />
                      </svg>
                      <span>Delete row</span>
                    </div>
                  </div>

                  <!-- row fields -->
                  <div class="grid auto-cols-fr grid-flow-col">
                    <div
                      v-for="field in row.fields"
                      :key="field.id"
                      class="border-e border-gray-200 last:border-e-0 dark:border-gray-700">
                      <!-- row options -->
                      <div class="flex justify-between border-b border-gray-200 px-3 py-1 text-xs dark:border-gray-700">
                        <div>
                          <div class="relative me-3 inline cursor-pointer">
                            <svg
                              class="me-1 inline h-3 w-3"
                              viewBox="0 0 24 24"
                              fill="none"
                              xmlns="http://www.w3.org/2000/svg">
                              <path
                                d="M12 6C12.5523 6 13 6.44772 13 7V11H17C17.5523 11 18 11.4477 18 12C18 12.5523 17.5523 13 17 13H13V17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17V13H7C6.44772 13 6 12.5523 6 12C6 11.4477 6.44772 11 7 11H11V7C11 6.44772 11.4477 6 12 6Z"
                                fill="currentColor" />
                              <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M5 22C3.34315 22 2 20.6569 2 19V5C2 3.34315 3.34315 2 5 2H19C20.6569 2 22 3.34315 22 5V19C22 20.6569 20.6569 22 19 22H5ZM4 19C4 19.5523 4.44772 20 5 20H19C19.5523 20 20 19.5523 20 19V5C20 4.44772 19.5523 4 19 4H5C4.44772 4 4 4.44772 4 5V19Z"
                                fill="currentColor" />
                            </svg>
                            <span>Change field type</span>
                          </div>
                        </div>

                        <div class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroyRow(row)">
                          <svg
                            class="me-1 inline h-3 w-3"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                              d="M16.3956 7.75734C16.7862 8.14786 16.7862 8.78103 16.3956 9.17155L13.4142 12.153L16.0896 14.8284C16.4802 15.2189 16.4802 15.8521 16.0896 16.2426C15.6991 16.6331 15.0659 16.6331 14.6754 16.2426L12 13.5672L9.32458 16.2426C8.93405 16.6331 8.30089 16.6331 7.91036 16.2426C7.51984 15.8521 7.51984 15.2189 7.91036 14.8284L10.5858 12.153L7.60436 9.17155C7.21383 8.78103 7.21383 8.14786 7.60436 7.75734C7.99488 7.36681 8.62805 7.36681 9.01857 7.75734L12 10.7388L14.9814 7.75734C15.372 7.36681 16.0051 7.36681 16.3956 7.75734Z"
                              fill="currentColor" />
                            <path
                              fill-rule="evenodd"
                              clip-rule="evenodd"
                              d="M4 1C2.34315 1 1 2.34315 1 4V20C1 21.6569 2.34315 23 4 23H20C21.6569 23 23 21.6569 23 20V4C23 2.34315 21.6569 1 20 1H4ZM20 3H4C3.44772 3 3 3.44772 3 4V20C3 20.5523 3.44772 21 4 21H20C20.5523 21 21 20.5523 21 20V4C21 3.44772 20.5523 3 20 3Z"
                              fill="currentColor" />
                          </svg>
                          <span>Delete field</span>
                        </div>
                      </div>

                      <!-- choose a field type -->
                      <div class="px-5 py-5">
                        <p>Choose a field type:</p>
                        <ul>
                          <li>
                            <span>Add a text field</span>
                          </li>
                          <li>Add a text area</li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- actions -->
            <div class="flex justify-between p-5">
              <pretty-link :href="data.url.back" :text="$t('Cancel')" :class="'me-3'" />
              <pretty-button
                :href="'data.url.vault.create'"
                :text="$t('Add')"
                :state="loadingState"
                :icon="'check'"
                :class="'save'" />
            </div>

            <!-- blank state -->
            <div class="mb-6">
              <p class="p-5 text-center">Please select a module on the left or create a new module.</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import PrettyLink from '@/Shared/Form/PrettyLink.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';

export default {
  components: {
    InertiaLink: Link,
    Layout,
    PrettyLink,
    PrettyButton,
    TextInput,
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
      addMode: false,
      relativeId: 0,
      localModules: [],
      realId: 0, // real id doesn't get updated when array is reordered. this is used to uniquely identify the item in the array.
      form: {
        search: '',
        name: '',
        rows: [],
        errors: [],
      },
    };
  },

  methods: {
    addRow() {
      this.relativeId = this.relativeId + 1;
      this.realId = this.realId + 1;

      this.form.rows.push({
        id: this.relativeId,
        realId: this.realId,
        fields: [
          {
            id: 1,
            name: 'sdsfa',
          },
        ],
      });
    },

    destroyRow(row) {
      var id = this.form.rows.findIndex((x) => x.id === row.id);
      this.form.rows.splice(id, 1);
    },

    addFieldToRight(row) {
      var id = this.form.rows.findIndex((x) => x.id === row.id);
      var highestId = this.form.rows[id].fields.reduce((a, b) => (Number(a.id) > Number(b.id) ? a : b));

      this.form.rows[id].fields.push({
        id: highestId + 1,
        name: 'sdkfjl',
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.grid-skeleton {
  grid-template-columns: 1fr 2fr;
}

@media (max-width: 480px) {
  .grid-skeleton {
    grid-template-columns: 1fr;
  }
}

.module-list {
  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
