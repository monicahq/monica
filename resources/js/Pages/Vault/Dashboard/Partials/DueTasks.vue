<script setup>
import { Link } from '@inertiajs/vue3';
import Avatar from '@/Shared/Avatar.vue';
import { LayoutList } from 'lucide-vue-next';
defineProps({
  data: Object,
});

const toggle = (task) => {
  axios.put(task.url.toggle).catch((error) => {
    this.form.errors = error.response.data;
  });
};
</script>

<template>
  <div class="mb-10">
    <h3 class="mb-3 flex items-center gap-2 border-b border-gray-200 pb-1 font-medium dark:border-gray-800">
      <LayoutList class="h-4 w-4 text-gray-600" />

      {{ $t('Due and upcoming tasks') }}
    </h3>

    <!-- list of tasks -->
    <div v-if="Object.keys(data.tasks).length > 0">
      <ul class="mb-4 rounded-lg border border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <li
          v-for="task in data.tasks"
          :key="task.id"
          class="item-list flex border-b border-gray-200 px-3 py-2 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-slate-900">
          <input
            :id="task.id"
            v-model="task.completed"
            :name="task.id"
            type="checkbox"
            class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-700 dark:bg-gray-900 dark:ring-offset-gray-800 dark:focus:ring-blue-700"
            @change="toggle(task)" />

          <div>
            <label :for="task.id" class="mb-2 ms-2 flex cursor-pointer text-gray-900 dark:text-gray-300">
              {{ task.label }}
            </label>

            <div class="flex items-center text-sm">
              <!-- due date -->
              <span
                v-if="task.due_at !== null"
                :class="
                  task.due_at.is_late
                    ? 'bg-red-400/10 text-red-600 dark:bg-red-600/10 dark:text-red-400'
                    : 'bg-sky-400/10 text-sky-600 dark:bg-sky-600/10 dark:text-sky-400'
                "
                class="me-4 ms-2 flex items-center rounded-full px-2 py-0.5 text-xs font-medium leading-5">
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
                <span>{{ task.due_at.formatted }}</span>
              </span>

              <!-- contact -->
              <div class="flex items-center">
                <Avatar :data="task.contact.avatar" :class="'me-2 h-5 w-5 rounded-full'" />

                <Link :href="task.contact.url.show" class="text-blue-500 hover:underline">
                  {{ task.contact.name }}
                </Link>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-else
      class="mb-2 flex items-center rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
      <img src="/img/dashboard_blank_tasks.svg" :alt="$t('Tasks')" class="me-2 h-14 w-14" />
      <p class="px-5 text-center">
        {{ $t('No tasks.') }}
      </p>
    </div>

    <div class="text-center">
      <Link
        :href="data.url.index"
        class="rounded-xs border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
        {{ $t('View all') }}
      </Link>
    </div>
  </div>
</template>

<style lang="scss" scoped>
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
