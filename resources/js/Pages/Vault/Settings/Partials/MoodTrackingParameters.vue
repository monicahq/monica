<script setup>
import { ref, nextTick, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import draggable from 'vuedraggable';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

const props = defineProps({
  data: Object,
});

const loadingState = ref('');
const localMoodTrackingParameters = ref(props.data.mood_tracking_parameters);
const createMoodTrackingParametersModalShown = ref(false);
const editMoodTrackingParameterId = ref(0);
const newMoodTrackingParameter = useTemplateRef('newMoodTrackingParameter');

const form = useForm({
  label: '',
  hex_color: '',
  position: '',
  errors: [],
});

const showMoodTrackingParameterModal = () => {
  form.label = '';
  form.position = '';
  createMoodTrackingParametersModalShown.value = true;

  nextTick().then(() => newMoodTrackingParameter.value.focus());
};

const renameMoodTrackingParameterModal = (moodTrackingParameter) => {
  form.label = moodTrackingParameter.label;
  form.hex_color = moodTrackingParameter.hex_color;
  editMoodTrackingParameterId.value = moodTrackingParameter.id;

  nextTick().then(() => newMoodTrackingParameter.value.focus());
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.mood_tracking_parameter_store, form)
    .then((response) => {
      localMoodTrackingParameters.value.push(response.data.data);
      loadingState.value = null;
      createMoodTrackingParametersModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const update = (moodTrackingParameter) => {
  loadingState.value = 'loading';

  axios
    .put(moodTrackingParameter.url.update, form)
    .then((response) => {
      localMoodTrackingParameters.value[
        localMoodTrackingParameters.value.findIndex((x) => x.id === moodTrackingParameter.id)
      ] = response.data.data;
      loadingState.value = null;
      editMoodTrackingParameterId.value = 0;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const destroy = (moodTrackingParameter) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(moodTrackingParameter.url.destroy)
      .then(() => {
        var id = localMoodTrackingParameters.value.findIndex((x) => x.id === moodTrackingParameter.id);
        localMoodTrackingParameters.value.splice(id, 1);
      })
      .catch((error) => {
        loadingState.value = null;
        form.errors = error.response.data;
      });
  }
};

const updatePosition = (event) => {
  // the event object comes from the draggable component
  form.position = event.moved.newIndex + 1;

  axios.put(event.moved.element.url.position, form).catch((error) => {
    loadingState.value = null;
    form.errors = error.response.data;
  });
};
</script>

<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="me-1"> 🤭 </span>
        {{ $t('Mood tracking parameters') }}
      </h3>
      <pretty-button
        v-if="!createMoodTrackingParametersModalShown"
        :text="$t('Add a parameter')"
        :icon="'plus'"
        @click="showMoodTrackingParameterModal" />
    </div>

    <!-- modal to create a mood tracking parameter -->
    <form
      v-if="createMoodTrackingParametersModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <text-input
          ref="newMoodTrackingParameter"
          v-model="form.label"
          :label="$t('Name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full mb-4'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="createMoodTrackingParametersModalShown = false" />

        <p class="mb-2 block text-sm">
          {{ $t('Choose a color') }}
        </p>
        <div class="grid grid-cols-8 gap-4">
          <div v-for="color in data.mood_tracking_parameter_colors" :key="color.hex_color" class="flex items-center">
            <input
              :id="color.hex_color"
              v-model="form.hex_color"
              :value="color.hex_color"
              name="name-order"
              type="radio"
              class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700"
              @click="form.hex_color = color.hex_color" />
            <label
              :for="color.hex_color"
              class="ms-2 inline-block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
              <div class="rounded-xs p-4" :class="color.hex_color" />
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createMoodTrackingParametersModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- help text -->
    <div
      class="mb-4 flex rounded-xs border border-gray-200 bg-slate-50 px-3 py-2 dark:border-gray-700 dark:bg-slate-900">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 pe-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>

      <div>
        <p>{{ $t('You can customize the criteria that let you track your mood.') }}</p>
      </div>
    </div>

    <!-- list of mood tracking parameters -->
    <div
      v-if="localMoodTrackingParameters.length > 0"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
      <draggable
        :list="localMoodTrackingParameters"
        item-key="id"
        :component-data="{ name: 'fade' }"
        handle=".handle"
        @change="updatePosition">
        <template #item="{ element }">
          <div
            v-if="editMoodTrackingParameterId !== element.id"
            class="item-list flex items-center justify-between border-b border-gray-200 py-2 pe-5 ps-4 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
            <!-- icon to move position -->
            <div class="me-2 flex items-center">
              <svg
                class="handle me-2 cursor-move"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M7 7H9V9H7V7Z" fill="currentColor" />
                <path d="M11 7H13V9H11V7Z" fill="currentColor" />
                <path d="M17 7H15V9H17V7Z" fill="currentColor" />
                <path d="M7 11H9V13H7V11Z" fill="currentColor" />
                <path d="M13 11H11V13H13V11Z" fill="currentColor" />
                <path d="M15 11H17V13H15V11Z" fill="currentColor" />
                <path d="M9 15H7V17H9V15Z" fill="currentColor" />
                <path d="M11 15H13V17H11V15Z" fill="currentColor" />
                <path d="M17 15H15V17H17V15Z" fill="currentColor" />
              </svg>

              <div class="me-2 inline-block h-4 w-4 rounded-full" :class="element.hex_color" />
              <span class="me-2">{{ element.label }}</span>
            </div>

            <!-- actions -->
            <ul class="text-sm">
              <li
                class="inline cursor-pointer text-blue-500 hover:underline"
                @click="renameMoodTrackingParameterModal(element)">
                {{ $t('Rename') }}
              </li>
              <li class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(element)">
                {{ $t('Delete') }}
              </li>
            </ul>
          </div>

          <form
            v-else
            class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
            @submit.prevent="update(element)">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <errors :errors="form.errors" />

              <text-input
                ref="newMoodTrackingParameter"
                v-model="form.label"
                :label="$t('Name')"
                :type="'text'"
                :autofocus="true"
                :input-class="'block w-full mb-4'"
                :required="true"
                :autocomplete="false"
                :maxlength="255"
                @esc-key-pressed="editMoodTrackingParameterId = 0" />

              <p class="mb-2 block text-sm">
                {{ $t('Choose a color') }}
              </p>
              <div class="grid grid-cols-8 gap-4">
                <div
                  v-for="color in data.mood_tracking_parameter_colors"
                  :key="color.hex_color"
                  class="flex items-center">
                  <input
                    :id="color.hex_color"
                    v-model="form.hex_color"
                    :value="color.hex_color"
                    name="name-order"
                    type="radio"
                    class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700"
                    @click="form.hex_color = color.hex_color" />
                  <label
                    :for="color.hex_color"
                    class="ms-2 inline-block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                    <div class="rounded-xs p-4" :class="color.hex_color" />
                  </label>
                </div>
              </div>
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="editMoodTrackingParameterId = 0" />
              <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
            </div>
          </form>
        </template>
      </draggable>
    </div>

    <!-- blank state -->
    <div
      v-if="localMoodTrackingParameters.length === 0"
      class="rounded-lg bg-white dark:border-gray-700 dark:bg-gray-900">
      <p class="p-5 text-center">Add at least one parameter to be able to track your mood.</p>
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
