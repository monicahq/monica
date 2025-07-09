<script setup>
import { nextTick, ref, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

const emit = defineEmits(['close', 'created', 'update:task']);

const props = defineProps({
  data: Object,
  task: Object,
});

const loadingState = ref('');
const labelInput = useTemplateRef('labelInput');
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});
const form = useForm({
  label: '',
  due_at: '',
  due_at_checked: false,
  errors: [],
});

const reset = () => {
  if (props.task) {
    form.label = props.task.label;
    form.due_at = props.task.due_at !== null ? props.task.due_at.value : null;
    form.due_at_checked = props.task.due_at !== null;
  } else {
    form.label = '';
    form.due_at = '';
    form.due_at_checked = false;
  }

  nextTick().then(() => labelInput.value.focus());
};

const submit = () => {
  loadingState.value = 'loading';

  let request = {
    method: 'post',
    url: props.data.url.store,
    data: form,
  };
  let event = 'created';

  if (props.task) {
    request.method = 'put';
    request.url = props.task.url.update;
    event = 'update:task';
  }

  axios
    .request(request)
    .then((response) => {
      loadingState.value = '';
      emit(event, response.data.data);
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

defineExpose({
  reset: () => reset(),
});
</script>

<template>
  <form
    class="rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
    @submit.prevent="submit()">
    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
      <errors :errors="form.errors" />

      <!-- title -->
      <text-input
        ref="labelInput"
        v-model="form.label"
        :label="$t('Title')"
        :type="'text'"
        :input-class="'block w-full mb-3'"
        :required="true"
        :autocomplete="false"
        :maxlength="255"
        @esc-key-pressed="$emit('close')" />
    </div>

    <!-- due date -->
    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
      <div class="flex items-center">
        <input
          id="reminder"
          v-model="form.due_at_checked"
          name="reminder"
          type="checkbox"
          class="focus:ring-3 relative h-4 w-4 rounded-xs border border-gray-300 bg-gray-50 focus:ring-blue-300 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600" />
        <label for="reminder" class="ms-2 block cursor-pointer text-sm text-gray-900 dark:text-gray-50">
          {{ $t('Add a due date') }}
        </label>
      </div>

      <!-- task options -->
      <div v-if="form.due_at_checked" class="ms-4 mt-4">
        <DatePicker
          v-model.string="form.due_at"
          class="inline-block h-full"
          :masks="masks"
          :locale="$page.props.auth.user?.locale_ietf"
          :is-dark="isDark()">
          <template #default="{ inputValue, inputEvents }">
            <input
              class="rounded-xs border bg-white px-2 py-1 dark:bg-gray-900"
              :value="inputValue"
              v-on="inputEvents" />
          </template>
        </DatePicker>
      </div>
    </div>

    <div class="flex justify-between p-5">
      <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="$emit('close')" />
      <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
    </div>
  </form>
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
