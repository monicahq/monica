<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title -->
        <div class="mb-5 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
          <div class="mb-2 sm:mb-0">
            <span class="relative">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-sidebar relative inline h-4 w-4 text-gray-300 hover:text-gray-600 dark:text-gray-700 hover:dark:text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </span>

            {{ $t('vault.tasks_title') }}
          </div>
        </div>

        <div v-for="task in data" :key="task.id" class="mb-6">
          <!-- person name -->
          <div class="mb-2 flex items-center">
            <avatar :data="task.contact.avatar" :classes="'mr-2 h-5 w-5 rounded-full'" />

            <inertia-link :href="task.contact.url.show" class="text-blue-500 hover:underline">
              {{ task.contact.name }}
            </inertia-link>
          </div>

          <!-- tasks -->
          <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="currentTask in task.tasks"
              :key="currentTask.id"
              class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-800 hover:dark:bg-slate-900">
              <div class="flex items-center">
                <input
                  :id="currentTask.id"
                  v-model="currentTask.completed"
                  :name="currentTask.id"
                  type="checkbox"
                  class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600"
                  @change="toggle(currentTask)" />

                <label
                  :for="currentTask.id"
                  class="ml-2 flex cursor-pointer items-center text-gray-900 dark:text-gray-100">
                  {{ currentTask.label }}

                  <!-- due date -->
                  <span
                    v-if="currentTask.due_at"
                    :class="
                      currentTask.due_at_late
                        ? 'bg-red-400/10 text-red-600 dark:bg-red-600/10 dark:text-red-400'
                        : 'bg-sky-400/10 text-sky-600 dark:bg-sky-600/10 dark:text-sky-400'
                    "
                    class="ml-2 flex items-center rounded-full px-2 py-0.5 text-xs font-medium leading-5">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="mr-1 h-3 w-3"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="2">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>

                    <span class="">{{ currentTask.due_at }}</span>
                  </span>
                </label>
              </div>
            </li>
          </ul>
        </div>

        <div
          v-if="data.length <= 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <p class="p-5 text-center">
            {{ $t('vault.tasks_blank') }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout.vue';
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
    Layout,
    Avatar,
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

  methods: {
    toggle(task) {
      axios.put(task.url.toggle).catch((error) => {
        this.form.errors = error.response.data;
      });
    },
  },
};
</script>

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
