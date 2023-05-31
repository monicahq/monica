<script setup>
import { nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/inertia-vue3';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import HoverMenu from '@/Shared/HoverMenu.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

const props = defineProps({
  data: Object,
});

const labelInput = ref(null);
const updateInput = ref([]);
const createTaskModalShown = ref(false);
const showCompletedTasks = ref(false);
const localTasks = ref(props.data.tasks);
const localCompletedTasks = ref([]);
const loadingState = ref('');
const editedTaskId = ref(0);
const dueDateShown = ref(false);
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});
const form = useForm({
  label: '',
  due_at: '',
  due_at_checked: false,
  errors: [],
});

const showCreateTaskModal = () => {
  form.errors = [];
  form.label = '';
  createTaskModalShown.value = true;
  form.due_at_checked = false;

  setTimeout(() => {
    nextTick(() => labelInput.value.focus());
  }, 150);
};

const showUpdateTaskModal = (task) => {
  form.errors = [];
  form.label = task.label;
  editedTaskId.value = task.id;
  form.due_at = task.due_at;
  form.due_at_checked = task.due_at !== '';

  setTimeout(() => {
    nextTick(() => updateInput.value.focus());
  }, 150);
};

const toggleDueDateModal = () => {
  dueDateShown.value = !dueDateShown.value;
};

const getCompleted = () => {
  axios.get(props.data.url.completed).then((response) => {
    localCompletedTasks.value = response.data.data;
    showCompletedTasks.value = true;
  });
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form)
    .then((response) => {
      loadingState.value = '';
      flash(trans('The task has been created'), 'success');
      localTasks.value.unshift(response.data.data);
      createTaskModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const update = (task) => {
  loadingState.value = 'loading';

  axios
    .put(task.url.update, form)
    .then((response) => {
      loadingState.value = '';
      flash(trans('The task has been edited'), 'success');
      localTasks.value[localTasks.value.findIndex((x) => x.id === task.id)] = response.data.data;
      editedTaskId.value = 0;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
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
      <div class="mb-2 sm:mb-0">
        <span class="relative me-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-sidebar relative inline h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
        </span>

        <span class="font-semibold"> {{ $t('Tasks') }} </span>
      </div>
      <pretty-button
        :text="$t('Add a task')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="showCreateTaskModal()" />
    </div>

    <!-- add a task modal -->
    <form
      v-if="createTaskModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <!-- title -->
        <text-input
          :ref="'labelInput'"
          v-model="form.label"
          :label="$t('Title')"
          :type="'text'"
          :input-class="'block w-full mb-3'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="createTaskModalShown = false" />
      </div>

      <!-- due date -->
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <div class="flex items-center">
          <input
            id="reminder"
            v-model="form.due_at_checked"
            name="reminder"
            type="checkbox"
            class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 focus:dark:ring-blue-600"
            @click="toggleDueDateModal" />
          <label for="reminder" class="ms-2 block cursor-pointer text-sm text-gray-900">
            {{ $t('Add a due date') }}
          </label>
        </div>

        <!-- task options -->
        <div v-if="form.due_at_checked" class="ms-4 mt-4">
          <DatePicker
            v-model.string="form.due_at"
            class="inline-block h-full"
            :masks="masks"
            :locale="$page.props.user.locale"
            :is-dark="isDark()">
            <template #default="{ inputValue, inputEvents }">
              <input
                class="rounded border bg-white px-2 py-1 dark:bg-gray-900"
                :value="inputValue"
                v-on="inputEvents" />
            </template>
          </DatePicker>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createTaskModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
      </div>
    </form>

    <!-- tasks -->
    <ul
      v-if="localTasks.length > 0"
      class="mb-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <li
        v-for="task in localTasks"
        :key="task.id"
        class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 hover:dark:bg-slate-800">
        <div v-if="editedTaskId !== task.id" class="flex items-center justify-between p-3">
          <div class="flex items-center">
            <input
              :id="task.id"
              v-model="task.completed"
              :name="task.id"
              type="checkbox"
              class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 focus:dark:ring-blue-600"
              @change="toggle(task)" />
            <label :for="task.id" class="ms-2 flex cursor-pointer text-gray-900">
              {{ task.label }}

              <!-- due date -->
              <span
                v-if="task.due_at"
                :class="task.due_at_late ? 'bg-red-400/10 text-red-600' : 'bg-sky-400/10 text-sky-600'"
                class="ms-2 flex items-center rounded-full px-2 py-0.5 text-xs font-medium leading-5 dark:text-sky-400">
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
                <span>{{ task.due_at }}</span>
              </span>
            </label>
          </div>

          <hover-menu :show-edit="true" :show-delete="true" @edit="showUpdateTaskModal(task)" @delete="destroy(task)" />
        </div>

        <!-- edit task -->
        <form v-if="editedTaskId === task.id" class="bg-gray-50 dark:bg-gray-900" @submit.prevent="update(task)">
          <errors :errors="form.errors" />

          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <text-input
              :ref="'updateInput'"
              v-model="form.label"
              :label="$t('Title')"
              :type="'text'"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="editedTaskId = 0" />
          </div>

          <!-- due date -->
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <div class="flex items-center">
              <input
                id="reminder"
                v-model="form.due_at_checked"
                name="reminder"
                type="checkbox"
                class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 focus:dark:ring-blue-600"
                @click="toggleDueDateModal" />
              <label for="reminder" class="ms-2 block cursor-pointer text-sm text-gray-900">
                {{ $t('Add a due date') }}
              </label>
            </div>

            <!-- task options -->
            <div v-if="form.due_at_checked" class="ms-4 mt-4">
              <DatePicker
                v-model.string="form.due_at"
                class="inline-block h-full"
                :masks="masks"
                :locale="$page.props.user.locale"
                :is-dark="isDark()">
                <template #default="{ inputValue, inputEvents }">
                  <input
                    class="rounded border bg-white px-2 py-1 dark:bg-gray-900"
                    :value="inputValue"
                    v-on="inputEvents" />
                </template>
              </DatePicker>
            </div>
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedTaskId = 0" />
            <pretty-button :text="$t('Update')" :state="loadingState" :icon="'check'" :class="'save'" />
          </div>
        </form>
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
          <div class="flex items-center justify-between p-3">
            <div class="flex items-center">
              <input
                :id="task.id"
                v-model="task.completed"
                :name="task.id"
                type="checkbox"
                class="focus:ring-3 relative h-4 w-4 rounded border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 focus:dark:ring-blue-600"
                @change="toggle(task)" />

              <label :for="task.id" class="ms-2 flex cursor-pointer items-center text-gray-900">
                {{ task.label }}

                <!-- due date -->
                <span
                  v-if="task.due_at"
                  :class="task.due_at_late ? 'bg-red-400/10' : ''"
                  class="ms-2 flex items-center rounded-full bg-sky-400/10 px-2 py-0.5 text-xs font-medium leading-5 text-sky-600 dark:text-sky-400">
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
                  <span class="">{{ task.due_at }}</span>
                </span>
              </label>
            </div>

            <hover-menu
              :show-edit="false"
              :show-delete="true"
              @edit="showUpdateTaskModal(task)"
              @delete="destroy(task)" />
          </div>
        </li>
      </ul>
    </div>

    <!-- blank state -->
    <div
      v-if="localTasks.length == 0"
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
