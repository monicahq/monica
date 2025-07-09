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
const createLifeEventCategoryModalShown = ref(false);
const createLifeEventTypeModalShown = ref(false);
const lifeEventCategoryId = ref(0);
const editLifeEventCategoryId = ref(0);
const editLifeEventTypeId = ref(0);
const localLifeEventCategories = ref(props.data.life_event_categories);
const newLifeEventCategory = useTemplateRef('newLifeEventCategory');
const renameLifeEventCategory = useTemplateRef('renameLifeEventCategory');
const newLifeEventType = useTemplateRef('newLifeEventType');
const renameLifeEventType = useTemplateRef('renameLifeEventType');

const form = useForm({
  label: '',
  position: '',
  errors: [],
});

const showCreateLifeEventCategoryModal = () => {
  form.label = '';
  form.position = '';
  createLifeEventCategoryModalShown.value = true;
  createLifeEventTypeModalShown.value = false;
  lifeEventCategoryId.value = 0;
  editLifeEventCategoryId.value = 0;
  editLifeEventTypeId.value = 0;

  nextTick().then(() => newLifeEventCategory.value.focus());
};

const showCreateLifeEventTypeModal = (lifeEventCategory) => {
  form.label = '';
  form.position = '';
  createLifeEventTypeModalShown.value = true;
  lifeEventCategoryId.value = lifeEventCategory.id;
  editLifeEventCategoryId.value = 0;
  editLifeEventTypeId.value = 0;
  createLifeEventCategoryModalShown.value = false;

  nextTick().then(() => newLifeEventType.value.focus());
};

const renameLifeEventCategoryModal = (lifeEventCategory) => {
  form.label = lifeEventCategory.label;
  editLifeEventCategoryId.value = lifeEventCategory.id;
  editLifeEventTypeId.value = 0;
  createLifeEventCategoryModalShown.value = false;
  createLifeEventTypeModalShown.value = false;
  lifeEventCategoryId.value = 0;

  nextTick().then(() => renameLifeEventCategory.value.focus());
};

const renameLifeEventTypeModal = (lifeEventType) => {
  form.label = lifeEventType.label;
  editLifeEventTypeId.value = lifeEventType.id;
  createLifeEventCategoryModalShown.value = false;
  createLifeEventTypeModalShown.value = false;
  editLifeEventCategoryId.value = 0;
  lifeEventCategoryId.value = 0;

  nextTick().then(() => renameLifeEventType.value.focus());
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.life_event_category_store, form)
    .then((response) => {
      localLifeEventCategories.value.push(response.data.data);
      loadingState.value = null;
      createLifeEventCategoryModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const update = (lifeEventCategory) => {
  loadingState.value = 'loading';

  axios
    .put(lifeEventCategory.url.update, form)
    .then((response) => {
      localLifeEventCategories.value[localLifeEventCategories.value.findIndex((x) => x.id === lifeEventCategory.id)] =
        response.data.data;
      loadingState.value = null;
      editLifeEventCategoryId.value = 0;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const destroy = (lifeEventCategory) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(lifeEventCategory.url.destroy)
      .then(() => {
        var id = localLifeEventCategories.value.findIndex((x) => x.id === lifeEventCategory.id);
        localLifeEventCategories.value.splice(id, 1);
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

  axios
    .post(event.moved.element.url.position, form)
    .then(() => {})
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const submitLifeEventType = (lifeEventCategory) => {
  loadingState.value = 'loading';

  axios
    .post(lifeEventCategory.url.store, form)
    .then((response) => {
      var id = localLifeEventCategories.value.findIndex((x) => x.id === lifeEventCategory.id);
      localLifeEventCategories.value[id].life_event_types.push(response.data.data);

      loadingState.value = null;
      lifeEventCategoryId.value = 0;
      createLifeEventTypeModalShown.value = false;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const updateLifeEventType = (lifeEventType) => {
  loadingState.value = 'loading';

  axios
    .put(lifeEventType.url.update, form)
    .then((response) => {
      var categoryId = localLifeEventCategories.value.findIndex((x) => x.id === lifeEventType.life_event_category_id);
      var typeId = localLifeEventCategories.value[categoryId].life_event_types.findIndex(
        (x) => x.id === lifeEventType.id,
      );
      localLifeEventCategories.value[categoryId].life_event_types[typeId] = response.data.data;

      loadingState.value = null;
      lifeEventCategoryId.value = 0;
      editLifeEventTypeId.value = 0;
    })
    .catch((error) => {
      loadingState.value = null;
      form.errors = error.response.data;
    });
};

const destroyLifeEventType = (lifeEventType) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(lifeEventType.url.destroy)
      .then(() => {
        var lifeEventCategoryId = localLifeEventCategories.value.findIndex(
          (x) => x.id === lifeEventType.life_event_category_id,
        );
        var lifeEventTypeId = localLifeEventCategories.value[lifeEventCategoryId].life_event_types.findIndex(
          (x) => x.id === lifeEventType.id,
        );
        localLifeEventCategories.value[lifeEventCategoryId].life_event_types.splice(lifeEventTypeId, 1);
      })
      .catch((error) => {
        loadingState.value = null;
        form.errors = error.response.data;
      });
  }
};
</script>

<template>
  <div class="mb-12">
    <!-- title + cta -->
    <div class="mb-3 mt-8 items-center justify-between sm:mt-0 sm:flex">
      <h3 class="mb-4 sm:mb-0">
        <span class="me-1"> 👩‍🍼 </span>
        {{ $t('Life event types and categories') }}
      </h3>
      <pretty-button
        v-if="!createLifeEventCategoryModalShown"
        :text="$t('Add a life event category')"
        :icon="'plus'"
        @click="showCreateLifeEventCategoryModal" />
    </div>

    <!-- modal to create a life event category -->
    <form
      v-if="createLifeEventCategoryModalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="submit()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <text-input
          ref="newLifeEventCategory"
          v-model="form.label"
          :label="$t('Name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="createLifeEventCategoryModalShown = false" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createLifeEventCategoryModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <!-- list of life event categories -->
    <div v-if="localLifeEventCategories.length > 0" class="mb-6">
      <draggable
        v-model="localLifeEventCategories"
        item-key="id"
        :component-data="{ name: 'fade' }"
        handle=".handle"
        @change="updatePosition">
        <template #item="{ element }">
          <div v-if="editLifeEventCategoryId !== element.id">
            <div
              class="item-list mb-2 rounded-lg border border-gray-200 bg-white py-2 pe-5 ps-4 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800">
              <div class="mb-3 flex items-center justify-between">
                <!-- icon to move position -->
                <div class="me-2 flex">
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

                  <span>{{ element.label }}</span>
                </div>

                <!-- actions -->
                <ul class="text-sm">
                  <li class="inline cursor-pointer" @click="renameLifeEventCategoryModal(element)">
                    <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                  </li>
                  <li class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(element)">
                    {{ $t('Delete') }}
                  </li>
                </ul>
              </div>

              <!-- available life event types -->
              <div class="ms-8">
                <p class="mb-1 text-sm text-gray-500">{{ $t('Life event types:') }}</p>

                <draggable
                  v-model="element.life_event_types"
                  item-key="id"
                  :component-data="{ name: 'fade' }"
                  handle=".handle"
                  @change="updatePosition">
                  <template #item="{ element: element2 }">
                    <div v-if="editLifeEventTypeId !== element2.id">
                      <div
                        class="item-list mb-2 rounded-lg border border-gray-200 bg-white py-2 pe-5 ps-4 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-slate-800">
                        <div class="flex items-center justify-between">
                          <!-- icon to move position -->
                          <div class="me-2 flex">
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

                            <span>{{ element2.label }}</span>
                          </div>

                          <!-- actions -->
                          <ul class="text-sm">
                            <li class="inline cursor-pointer" @click="renameLifeEventTypeModal(element2)">
                              <span class="text-blue-500 hover:underline">{{ $t('Rename') }}</span>
                            </li>
                            <li
                              class="ms-4 inline cursor-pointer text-red-500 hover:text-red-900"
                              @click="destroyLifeEventType(element2)">
                              {{ $t('Delete') }}
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>

                    <!-- edit a life event type form -->
                    <form
                      v-else
                      class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                      @submit.prevent="updateLifeEventType(element2)">
                      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <errors :errors="form.errors" />

                        <text-input
                          ref="renameLifeEventType"
                          v-model="form.label"
                          :label="$t('Name')"
                          :type="'text'"
                          :autofocus="true"
                          :input-class="'block w-full'"
                          :required="true"
                          :autocomplete="false"
                          :maxlength="255"
                          @esc-key-pressed="editLifeEventTypeId = 0" />
                      </div>

                      <div class="flex justify-between p-5">
                        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editLifeEventTypeId = 0" />
                        <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
                      </div>
                    </form>
                  </template>
                </draggable>

                <!-- add a life event type -->
                <span
                  v-if="
                    element.life_event_types.length !== 0 &&
                    !createLifeEventTypeModalShown &&
                    lifeEventCategoryId !== element.id
                  "
                  class="inline cursor-pointer text-sm text-blue-500 hover:underline"
                  @click="showCreateLifeEventTypeModal(element)">
                  {{ $t('add a life event type') }}
                </span>

                <!-- form: create new life event type -->
                <form
                  v-if="createLifeEventTypeModalShown && lifeEventCategoryId === element.id"
                  class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                  @submit.prevent="submitLifeEventType(element)">
                  <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <errors :errors="form.errors" />

                    <text-input
                      ref="newLifeEventType"
                      v-model="form.label"
                      :label="$t('Name')"
                      :type="'text'"
                      :autofocus="true"
                      :input-class="'block w-full'"
                      :required="true"
                      :autocomplete="false"
                      :maxlength="255"
                      @esc-key-pressed="
                        createLifeEventTypeModalShown = false;
                        lifeEventCategoryId = 0;
                      " />
                  </div>

                  <div class="flex justify-between p-5">
                    <pretty-span
                      :text="$t('Cancel')"
                      :class="'me-3'"
                      @click="
                        createLifeEventTypeModalShown = false;
                        lifeEventCategoryId = 0;
                      " />
                    <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
                  </div>
                </form>

                <!-- blank state -->
                <div
                  v-if="
                    element.life_event_types.length === 0 &&
                    !createLifeEventTypeModalShown &&
                    lifeEventCategoryId !== element.id
                  "
                  class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                  <p class="p-5 text-center">
                    {{ $t('No life event types yet.') }}

                    <span
                      class="block cursor-pointer text-sm text-blue-500 hover:underline"
                      @click="showCreateLifeEventTypeModal(element)"
                      >{{ $t('add a life event type') }}</span
                    >
                  </p>
                </div>
              </div>
            </div>
          </div>

          <form
            v-else
            class="item-list mb-2 rounded-lg border border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800"
            @submit.prevent="update(element)">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <errors :errors="form.errors" />

              <text-input
                ref="renameLifeEventCategory"
                v-model="form.label"
                :label="$t('Name')"
                :type="'text'"
                :autofocus="true"
                :input-class="'block w-full'"
                :required="true"
                :autocomplete="false"
                :maxlength="255"
                @esc-key-pressed="editLifeEventCategoryId = 0" />
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" :class="'me-3'" @click.prevent="editLifeEventCategoryId = 0" />
              <pretty-button :text="$t('Rename')" :state="loadingState" :icon="'check'" :class="'save'" />
            </div>
          </form>
        </template>
      </draggable>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
