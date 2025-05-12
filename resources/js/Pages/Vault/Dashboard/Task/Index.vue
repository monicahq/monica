<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <main class="relative sm:mt-24">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title -->
        <div class="mb-5 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
          <div class="mb-2 sm:mb-0 flex items-center gap-2">
            <LayoutList class="h-4 w-4 text-gray-600" />

            {{ $t('Due and upcoming tasks') }}
          </div>
        </div>

        <div v-for="task in data" :key="task.id" class="mb-6">
          <!-- person name -->
          <div class="mb-2 flex items-center">
            <avatar :data="task.contact.avatar" :class="'me-2 h-5 w-5 rounded-full'" />

            <InertiaLink :href="task.contact.url.show" class="text-blue-500 hover:underline">
              {{ task.contact.name }}
            </InertiaLink>
          </div>

          <!-- tasks -->
          <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="currentTask in task.tasks"
              :key="currentTask.id"
              class="item-list border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-slate-900">
              <div class="flex items-center">
                <input
                  :id="currentTask.id"
                  v-model="currentTask.completed"
                  :name="currentTask.id"
                  type="checkbox"
                  class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600"
                  @change="toggle(currentTask)" />

                <label
                  :for="currentTask.id"
                  class="ms-2 flex cursor-pointer items-center text-gray-900 dark:text-gray-100">
                  {{ currentTask.label }}

                  <!-- due date -->
                  <span
                    v-if="currentTask.due_at !== null"
                    :class="
                      currentTask.due_at.is_late
                        ? 'bg-red-400/10 text-red-600 dark:bg-red-600/10 dark:text-red-400'
                        : 'bg-sky-400/10 text-sky-600 dark:bg-sky-600/10 dark:text-sky-400'
                    "
                    class="ms-2 flex items-center rounded-full px-2 py-0.5 text-xs font-medium leading-5">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="me-1 h-3 w-3"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="2">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>

                    <span>{{ currentTask.due_at.formatted }}</span>
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
            {{ $t('No tasks.') }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import Avatar from '@/Shared/Avatar.vue';
import { LayoutList } from 'lucide-vue-next';

export default {
  components: {
    InertiaLink: Link,
    Layout,
    Avatar,
    LayoutList,
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
