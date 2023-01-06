<template>
  <div class="mb-10">
    <h3 class="mb-3 border-b border-gray-200 pb-1 font-medium dark:border-gray-800">
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

      {{ $t('vault.dashboard_due_tasks_title') }}
    </h3>

    <!-- list of tasks -->
    <div v-if="data.tasks.length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <li
          v-for="task in data.tasks"
          :key="task.id"
          class="item-list flex border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-800 hover:dark:bg-slate-900">
          <input
            :id="task.id"
            v-model="task.completed"
            :name="task.id"
            type="checkbox"
            class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-700 dark:bg-gray-900 dark:ring-offset-gray-800 focus:dark:ring-blue-700"
            @change="toggle(task)" />

          <div>
            <label :for="task.id" class="ml-2 mb-2 flex cursor-pointer text-gray-900 dark:text-gray-300">
              {{ task.label }}
            </label>

            <div class="flex items-center text-sm">
              <!-- due date -->
              <span
                v-if="task.due_at"
                :class="
                  task.due_at_late
                    ? 'bg-red-400/10 text-red-600 dark:bg-red-600/10 dark:text-red-400'
                    : 'bg-sky-400/10 text-sky-600 dark:bg-sky-600/10 dark:text-sky-400'
                "
                class="ml-2 mr-4 flex items-center rounded-full px-2 py-0.5 text-xs font-medium leading-5 dark:text-sky-400">
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
                <span class="">{{ task.due_at }}</span>
              </span>

              <!-- contact -->
              <div class="flex items-center">
                <avatar :data="task.contact.avatar" :classes="'mr-2 h-5 w-5 rounded-full'" />

                <inertia-link :href="task.contact.url.show" class="text-blue-500 hover:underline">
                  {{ task.contact.name }}
                </inertia-link>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="data.tasks.length == 0"
      class="mb-6 flex items-center rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
      <img src="/img/dashboard_blank_tasks.svg" :alt="$t('Tasks')" class="mr-2 h-14 w-14" />
      <p class="px-5 text-center">
        {{ $t('vault.dashboard_due_tasks_blank') }}
      </p>
    </div>

    <div class="text-center">
      <inertia-link
        :href="data.url.index"
        class="rounded border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
        {{ $t('app.view_all') }}
      </inertia-link>
    </div>
  </div>
</template>

<script>
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
    Avatar,
  },

  props: {
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

input[type='checkbox'] {
  top: 4px;
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
