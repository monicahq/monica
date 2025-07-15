<script setup>
import { nextTick, ref, useTemplateRef } from 'vue';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import HoverMenu from '@/Shared/HoverMenu.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import CreateOrEditTask from '@/Shared/Modules/TaskItems/CreateOrEditTask.vue';
import DateIcon from '@/Shared/Icons/DateIcon.vue';
import { LayoutList } from 'lucide-vue-next';

const props = defineProps({
  data: Object,
});

const createTaskForm = useTemplateRef('createTaskForm');
const updateTaskForm = useTemplateRef('updateTaskForm');
const updateCompletedTaskForm = useTemplateRef('updateCompletedTaskForm');
const createTaskModalShown = ref(false);
const showCompletedTasks = ref(false);
const localTasks = ref(props.data.tasks);
const localCompletedTasks = ref([]);
const editedTaskId = ref(0);
const editedCompletedTaskId = ref(0);

const showCreateTaskModal = () => {
  createTaskModalShown.value = true;

  nextTick().then(() => createTaskForm.value.reset());
};

const showUpdateTaskModal = (task) => {
  editedTaskId.value = task.id;

  nextTick().then(() => updateTaskForm.value[0].reset());
};

const showUpdateCompletedTaskModal = (task) => {
  editedCompletedTaskId.value = task.id;

  nextTick().then(() => updateCompletedTaskForm.value[0].reset());
};

const getCompleted = () => {
  axios.get(props.data.url.completed).then((response) => {
    localCompletedTasks.value = response.data.data;
    showCompletedTasks.value = true;
  });
};

const created = (task) => {
  flash(trans('The task has been created'), 'success');
  localTasks.value.unshift(task);
  createTaskModalShown.value = false;
};

const updated = (task) => {
  flash(trans('The task has been updated'), 'success');
  localTasks.value[localTasks.value.findIndex((x) => x.id === task.id)] = task;
  editedTaskId.value = 0;
};

const updatedCompleted = (task) => {
  flash(trans('The task has been updated'), 'success');
  localCompletedTasks.value[localCompletedTasks.value.findIndex((x) => x.id === task.id)] = task;
  editedCompletedTaskId.value = 0;
};

const toggle = (task) => {
  axios.put(task.url.toggle).catch((error) => {
    form.errors = error.response.data;
  });
};

const destroy = (task) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(task.url.destroy)
      .then(() => {
        flash(trans('The task has been deleted'), 'success');
        var id = localTasks.value.findIndex((x) => x.id === task.id);
        localTasks.value.splice(id, 1);
      })
      .catch((error) => {
        form.errors = error.response.data;
      });
  }
};
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <LayoutList class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Tasks') }} </span>
      </div>
      <PrettyButton :text="$t('Add a task')" :icon="'plus'" :class="'w-full sm:w-fit'" @click="showCreateTaskModal()" />
    </div>

    <!-- add a task modal -->
    <CreateOrEditTask
      class="mb-6"
      v-if="createTaskModalShown"
      :data="data"
      ref="createTaskForm"
      @created="created"
      @close="createTaskModalShown = false" />

    <!-- tasks -->
    <ul
      v-if="localTasks.length > 0"
      class="mb-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <li
        v-for="task in localTasks"
        :key="task.id"
        class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
        <div v-if="editedTaskId !== task.id" class="flex items-center justify-between p-3">
          <div class="flex items-center">
            <input
              :id="task.id"
              v-model="task.completed"
              :name="task.id"
              type="checkbox"
              class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600"
              @change="toggle(task)" />
            <label :for="task.id" class="ms-2 flex cursor-pointer text-gray-900 dark:text-gray-50">
              {{ task.label }}

              <!-- due date -->
              <span
                v-if="task.due_at !== null"
                :class="
                  task.due_at.is_late
                    ? 'bg-red-400/10 text-red-600 dark:bg-red-600/10 dark:text-red-400'
                    : 'bg-sky-400/10 text-sky-600 dark:bg-sky-600/10 dark:text-sky-400'
                "
                class="ms-2 flex items-center rounded-full px-2 py-0.5 text-xs font-medium leading-5">
                <DateIcon />
                <span>{{ task.due_at.formatted }}</span>
              </span>
            </label>
          </div>

          <HoverMenu :show-edit="true" :show-delete="true" @edit="showUpdateTaskModal(task)" @delete="destroy(task)" />
        </div>

        <!-- edit task -->
        <CreateOrEditTask
          class="mb-3"
          v-if="editedTaskId === task.id"
          ref="updateTaskForm"
          :data="data"
          :task="task"
          @update:task="updated"
          @close="editedTaskId = 0" />
      </li>
    </ul>

    <!-- button to display completed tasks -->
    <p
      v-if="data.completed_tasks_count > 0 && !showCompletedTasks"
      class="mx-4 mb-6 cursor-pointer text-xs text-blue-500 hover:underline"
      @click="getCompleted()">
      {{ $t('Show completed tasks (:count)', { count: data.completed_tasks_count }) }}
    </p>

    <!-- list of completed tasks -->
    <div v-if="showCompletedTasks" class="mx-4 text-xs">
      <ul
        v-for="task in localCompletedTasks"
        :key="task.id"
        class="mb-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li>
          <div v-if="editedCompletedTaskId !== task.id" class="flex items-center justify-between p-3">
            <div class="flex items-center">
              <input
                :id="task.id"
                v-model="task.completed"
                :name="task.id"
                type="checkbox"
                class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600"
                @change="toggle(task)" />

              <label :for="task.id" class="ms-2 flex cursor-pointer items-center text-gray-900 dark:text-gray-50">
                {{ task.label }}

                <!-- due date -->
                <span
                  v-if="task.due_at !== null"
                  class="ms-2 flex items-center rounded-full bg-sky-400/10 px-2 py-0.5 text-xs font-medium leading-5 text-sky-600 dark:text-sky-400">
                  <DateIcon />
                  <span>{{ task.due_at.formatted }}</span>
                </span>
              </label>
            </div>

            <HoverMenu
              :show-edit="true"
              :show-delete="true"
              @edit="showUpdateCompletedTaskModal(task)"
              @delete="destroy(task)" />
          </div>

          <!-- edit task -->
          <CreateOrEditTask
            v-if="editedCompletedTaskId === task.id"
            ref="updateCompletedTaskForm"
            :data="data"
            :task="task"
            @update:task="updatedCompleted"
            @close="editedCompletedTaskId = 0" />
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="localTasks.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/dashboard_blank_tasks.svg" :alt="$t('Tasks')" class="mx-auto mt-4 h-14 w-14" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no tasks yet.') }}</p>
    </div>
  </div>
</template>

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

input[type='checkbox'] {
  top: -1px;
}
</style>
