<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { flash } from '@/methods';
import { trans } from 'laravel-vue-i18n';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import HoverMenu from '@/Shared/HoverMenu.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Errors from '@/Shared/Form/Errors.vue';
import { PhoneCall } from 'lucide-vue-next';

const props = defineProps({
  data: Object,
});

const createCallModalShown = ref(false);
const localCalls = ref(props.data.calls);
const loadingState = ref('');
const editedCallId = ref(0);
const descriptionFieldShown = ref(false);
const reasonFieldShown = ref(false);
const emotionFieldShown = ref(false);
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});
const form = useForm({
  who_initiated: 'me',
  called_at: '',
  call_reason_id: 0,
  description: '',
  emotion_id: 0,
  type: 'audio',
  errors: [],
});

const showDescriptionField = () => {
  descriptionFieldShown.value = true;
  form.description = '';
};

const showReasonField = () => {
  reasonFieldShown.value = true;
  form.call_reason_id = '';
};

const showEmotionField = () => {
  emotionFieldShown.value = true;
  form.emotion_id = 1;
};

const showCreateCallModal = () => {
  form.errors = [];
  descriptionFieldShown.value = false;
  reasonFieldShown.value = false;
  createCallModalShown.value = true;
};

const showUpdateCallModal = (call) => {
  form.errors = [];
  form.description = call.description;

  if (call.description) {
    descriptionFieldShown.value = true;
  }

  form.type = call.type;

  if (!call.answered && call.who_initiated === 'me') {
    form.who_initiated = 'me_not_answered';
  } else if (call.answered && call.who_initiated === 'me') {
    form.who_initiated = 'me';
  } else if (call.answered && call.who_initiated === 'contact') {
    form.who_initiated = 'contact';
  } else if (!call.answered && call.who_initiated === 'contact') {
    form.who_initiated = 'contact_not_answered';
  }

  if (call.reason) {
    form.call_reason_id = call.reason.id;
    reasonFieldShown.value = true;
  } else {
    form.call_reason_id = 0;
    reasonFieldShown.value = false;
  }

  if (call.emotion) {
    form.emotion_id = call.emotion.id;
    emotionFieldShown.value = true;
  } else {
    form.emotion_id = 0;
    emotionFieldShown.value = false;
  }

  form.called_at = call.called_at;
  editedCallId.value = call.id;
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form)
    .then((response) => {
      loadingState.value = '';
      flash(trans('The call has been created'), 'success');
      localCalls.value.unshift(response.data.data);
      createCallModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const update = (call) => {
  loadingState.value = 'loading';

  axios
    .put(call.url.update, form)
    .then((response) => {
      loadingState.value = '';
      flash(trans('The call has been updated'), 'success');
      localCalls.value[localCalls.value.findIndex((x) => x.id === call.id)] = response.data.data;
      editedCallId.value = 0;
    })
    .catch((error) => {
      loadingState.value = '';
      form.errors = error.response.data;
    });
};

const destroy = (call) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(call.url.destroy)
      .then(() => {
        flash(trans('The call has been deleted'), 'success');
        let id = localCalls.value.findIndex((x) => x.id === call.id);
        localCalls.value.splice(id, 1);
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
        <PhoneCall class="h-4 w-4 text-gray-600" />

        <span class="font-semibold"> {{ $t('Calls') }} </span>
      </div>
      <pretty-button
        :text="$t('Log a call')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="showCreateCallModal()" />
    </div>

    <!-- add a call modal -->
    <form
      v-if="createCallModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div>
        <div v-if="form.errors.length > 0" class="p-5">
          <errors :errors="form.errors" />
        </div>

        <!-- date -->
        <div class="flex border-b border-gray-200 dark:border-gray-700">
          <div class="p-5">
            <p class="mb-2 block text-sm">{{ $t('When did the call happened?') }}</p>
            <DatePicker
              v-model.string="form.called_at"
              class="inline-block h-full"
              :masks="masks"
              :locale="$page.props.auth.user?.locale_ietf"
              :is-dark="isDark()"
              :max-date="new Date()">
              <template #default="{ inputValue, inputEvents }">
                <input
                  class="rounded-xs border bg-white px-2 py-1 dark:bg-gray-900"
                  :value="inputValue"
                  v-on="inputEvents" />
              </template>
            </DatePicker>
          </div>

          <!-- audio or video -->
          <div class="border-e border-gray-200 p-5 dark:border-gray-700">
            <p class="mb-2 block text-sm">{{ $t('Nature of the call') }}</p>

            <div class="flex">
              <div class="me-6 flex items-center">
                <input
                  id="audio"
                  v-model="form.type"
                  value="audio"
                  name="type"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  for="audio"
                  class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('Audio-only call') }}
                </label>
              </div>

              <div class="flex items-center">
                <input
                  id="video"
                  v-model="form.type"
                  value="video"
                  name="type"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                <label
                  for="video"
                  class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t('Video call') }}
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- who called -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <p class="mb-2 block text-sm">{{ $t('Who called?') }}</p>

          <div class="mb-4 flex">
            <div class="me-6 flex items-center">
              <input
                id="me"
                v-model="form.who_initiated"
                value="me"
                name="who_initiated"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
              <label for="me" class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t('I called') }}
              </label>
            </div>

            <div class="flex items-center">
              <input
                id="me_not_answered"
                v-model="form.who_initiated"
                value="me_not_answered"
                name="who_initiated"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
              <label
                for="me_not_answered"
                class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t('I called, but :name didn’t answer', { name: data.contact_name }) }}
              </label>
            </div>
          </div>

          <div class="flex">
            <div class="me-6 flex items-center">
              <input
                id="contact"
                v-model="form.who_initiated"
                value="contact"
                name="who_initiated"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
              <label
                for="contact"
                class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t(':Name called', { name: data.contact_name }) }}
              </label>
            </div>

            <div class="flex items-center">
              <input
                id="contact_not_answered"
                v-model="form.who_initiated"
                value="contact_not_answered"
                name="who_initiated"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
              <label
                for="contact_not_answered"
                class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t(':Name called, but I didn’t answer', { name: data.contact_name }) }}
              </label>
            </div>
          </div>
        </div>

        <!-- description field -->
        <div v-if="descriptionFieldShown" class="border-b border-gray-200 p-5 dark:border-gray-700">
          <text-area
            v-model="form.description"
            :label="$t('Description')"
            :rows="10"
            :required="false"
            :maxlength="65535"
            :textarea-class="'block w-full mb-3'" />
        </div>

        <!-- reason field -->
        <div v-if="reasonFieldShown" class="border-b border-gray-200 p-5 dark:border-gray-700">
          <p class="mb-2 block text-sm">{{ $t('Was there a reason for the call?') }}</p>
          <select
            id="types"
            v-model="form.call_reason_id"
            name="types"
            class="w-full rounded-md border-gray-300 bg-white px-3 py-2 shadow-xs focus:border-indigo-300 focus:outline-hidden focus:ring-3 focus:ring-indigo-200/50 dark:bg-gray-900 sm:text-sm">
            <optgroup
              v-for="callReasonType in data.call_reason_types"
              :key="callReasonType.id"
              :label="callReasonType.label">
              <option v-for="reason in callReasonType.reasons" :key="reason.id" :value="reason.id">
                {{ reason.label }}
              </option>
            </optgroup>
          </select>
        </div>

        <!-- emotion -->
        <div v-if="emotionFieldShown" class="border-b border-gray-200 p-5 dark:border-gray-700">
          <p class="mb-2">{{ $t('How did you feel?') }}</p>
          <div v-for="emotion in data.emotions" :key="emotion.id" class="mb-2 flex items-center">
            <input
              :id="emotion.type"
              v-model="form.emotion_id"
              :value="emotion.id"
              name="emotion"
              type="radio"
              class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500" />
            <label :for="emotion.type" class="ms-2 block cursor-pointer font-medium text-gray-700 dark:text-gray-300">
              {{ emotion.name }}
            </label>
          </div>
        </div>

        <!-- options -->
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <!-- cta to add a description -->
          <span
            v-if="!descriptionFieldShown"
            class="me-2 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
            @click="showDescriptionField">
            {{ $t('+ add description') }}
          </span>

          <!-- cta to add a reason -->
          <span
            v-if="!reasonFieldShown"
            class="me-2 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
            @click="showReasonField">
            {{ $t('+ add reason') }}
          </span>

          <!-- cta to add emotion -->
          <span
            v-if="!emotionFieldShown"
            class="inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
            @click="showEmotionField">
            {{ $t('+ add emotion') }}
          </span>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createCallModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- calls -->
    <ul
      v-if="localCalls.length > 0"
      class="mb-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <li
        v-for="call in localCalls"
        :key="call.id"
        class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
        <div v-if="editedCallId !== call.id" class="flex items-center justify-between p-3">
          <div class="flex items-center">
            <div>
              <CallIcon :answered="call.answered" />
            </div>

            <span class="me-2 text-sm text-gray-500">{{ call.called_at }}</span>

            <!-- who called -->
            <span
              v-if="call.who_initiated === 'me'"
              class="me-2 rounded-xs border border-neutral-200 px-2 py-1 text-xs font-semibold text-neutral-800">
              {{ $t('I called') }}
            </span>
            <span
              v-else
              class="me-2 rounded-xs border border-neutral-200 px-2 py-1 text-xs font-semibold text-neutral-800">
              {{ $t(':Name called', { name: data.contact_name }) }}
            </span>

            <!-- reason, if defined -->
            <span v-if="call.reason">{{ call.reason.label }}</span>

            <!-- emotion -->
            <div v-if="call.emotion" class="text-xs text-gray-600 dark:text-gray-400">
              {{ call.emotion.name }}
            </div>
          </div>

          <hover-menu :show-edit="true" :show-delete="true" @edit="showUpdateCallModal(call)" @delete="destroy(call)" />
        </div>

        <!-- edit call -->
        <form v-if="editedCallId === call.id" class="bg-gray-50 dark:bg-gray-900" @submit.prevent="update(call)">
          <errors :errors="form.errors" />

          <div class="border-b border-gray-200 dark:border-gray-700">
            <!-- date -->
            <div class="flex border-b border-gray-200 dark:border-gray-700">
              <div class="p-5">
                <p class="mb-2 block text-sm">When did the call happened?</p>
                <DatePicker
                  v-model.string="form.called_at"
                  class="inline-block h-full"
                  :masks="masks"
                  :is-dark="isDark()"
                  :max-date="new Date()">
                  <template #default="{ inputValue, inputEvents }">
                    <input
                      class="rounded-xs border bg-white px-2 py-1 dark:bg-gray-900"
                      :value="inputValue"
                      v-on="inputEvents" />
                  </template>
                </DatePicker>
              </div>

              <!-- audio or video -->
              <div class="border-e border-gray-200 p-5 dark:border-gray-700">
                <p class="mb-2 block text-sm">{{ $t('Nature of the call') }}</p>

                <div class="flex">
                  <div class="me-6 flex items-center">
                    <input
                      id="audio"
                      v-model="form.type"
                      value="audio"
                      name="type"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                    <label
                      for="audio"
                      class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('Audio-only call') }}
                    </label>
                  </div>

                  <div class="flex items-center">
                    <input
                      id="video"
                      v-model="form.type"
                      value="video"
                      name="type"
                      type="radio"
                      class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                    <label
                      for="video"
                      class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('Video call') }}
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- who called -->
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <p class="mb-2 block text-sm">{{ $t('Who called?') }}</p>

              <div class="mb-4 flex">
                <div class="me-6 flex items-center">
                  <input
                    id="me"
                    v-model="form.who_initiated"
                    value="me"
                    name="who_initiated"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                  <label
                    for="me"
                    class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('I called') }}
                  </label>
                </div>

                <div class="flex items-center">
                  <input
                    id="me_not_answered"
                    v-model="form.who_initiated"
                    value="me_not_answered"
                    name="who_initiated"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                  <label
                    for="me_not_answered"
                    class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('I called, but :name didn’t answer', { name: data.contact_name }) }}
                  </label>
                </div>
              </div>

              <div class="flex">
                <div class="me-6 flex items-center">
                  <input
                    id="contact"
                    v-model="form.who_initiated"
                    value="contact"
                    name="who_initiated"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                  <label
                    for="contact"
                    class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t(':Name called', { name: data.contact_name }) }}
                  </label>
                </div>

                <div class="flex items-center">
                  <input
                    id="contact_not_answered"
                    v-model="form.who_initiated"
                    value="contact_not_answered"
                    name="who_initiated"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
                  <label
                    for="contact_not_answered"
                    class="ms-2 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t(':Name called, but I didn’t answer', { name: data.contact_name }) }}
                  </label>
                </div>
              </div>
            </div>

            <!-- description field -->
            <div v-if="descriptionFieldShown" class="border-b border-gray-200 p-5 dark:border-gray-700">
              <text-area
                v-model="form.description"
                :label="$t('Description')"
                :rows="10"
                :required="false"
                :maxlength="65535"
                :textarea-class="'block w-full mb-3'" />
            </div>

            <!-- reason field -->
            <div v-if="reasonFieldShown" class="border-b border-gray-200 p-5 dark:border-gray-700">
              <p class="mb-2 block text-sm">{{ $t('Was there a reason for the call?') }}</p>
              <select
                id="types"
                v-model="form.call_reason_id"
                name="types"
                class="w-full rounded-md border-gray-300 bg-white px-3 py-2 shadow-xs focus:border-indigo-300 focus:outline-hidden focus:ring-3 focus:ring-indigo-200/50 dark:bg-gray-900 sm:text-sm">
                <optgroup
                  v-for="callReasonType in data.call_reason_types"
                  :key="callReasonType.id"
                  :label="callReasonType.label">
                  <option v-for="reason in callReasonType.reasons" :key="reason.id" :value="reason.id">
                    {{ reason.label }}
                  </option>
                </optgroup>
              </select>
            </div>

            <!-- emotion -->
            <div v-if="emotionFieldShown" class="border-b border-gray-200 p-5 dark:border-gray-700">
              <p class="mb-2">{{ $t('How did you feel?') }}</p>
              <div v-for="emotion in data.emotions" :key="emotion.id" class="mb-2 flex items-center">
                <input
                  :id="emotion.type"
                  v-model="form.emotion_id"
                  :value="emotion.id"
                  name="emotion"
                  type="radio"
                  class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                <label
                  :for="emotion.type"
                  class="ms-2 block cursor-pointer font-medium text-gray-700 dark:text-gray-300">
                  {{ emotion.name }}
                </label>
              </div>
            </div>

            <!-- options -->
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <!-- cta to add a description -->
              <span
                v-if="!descriptionFieldShown"
                class="me-2 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
                @click="showDescriptionField">
                {{ $t('+ add description') }}
              </span>

              <!-- cta to add a reason -->
              <span
                v-if="!reasonFieldShown"
                class="me-2 inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
                @click="showReasonField">
                {{ $t('+ add reason') }}
              </span>

              <!-- cta to add emotion -->
              <span
                v-if="!emotionFieldShown"
                class="inline-block cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-xs hover:bg-slate-300"
                @click="showEmotionField">
                {{ $t('+ add emotion') }}
              </span>
            </div>
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedCallId = 0" />
            <pretty-button :text="$t('Update')" :state="loadingState" :icon="'check'" :class="'save'" />
          </div>
        </form>
      </li>
    </ul>

    <!-- blank state -->
    <div
      v-if="localCalls.length === 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <img src="/img/contact_blank_call.svg" :alt="$t('Calls')" class="mx-auto mt-4 h-20 w-20" />
      <p class="px-5 pb-5 pt-2 text-center">{{ $t('There are no calls logged yet.') }}</p>
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
</style>
