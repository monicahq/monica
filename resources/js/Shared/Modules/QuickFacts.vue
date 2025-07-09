<script setup>
import { ref, nextTick, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Loading from '@/Shared/Loading.vue';
import HoverMenu from '@/Shared/HoverMenu.vue';
import Errors from '@/Shared/Form/Errors.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import ChevronIcon from '@/Shared/Icons/ChevronIcon.vue';
import ValideIcon from '@/Shared/Icons/ValideIcon.vue';
import { Lightbulb } from 'lucide-vue-next';

const props = defineProps({
  data: Object,
});

const form = useForm({
  content: '',
});

const loading = ref(false);
const loadingState = ref(false);
const createQuickFactModalShown = ref(false);
const openState = ref(props.data.show_quick_facts);
const localQuickFacts = ref(props.data.quick_facts.quick_facts);
const localTemplate = ref(props.data.quick_facts.template);
const contentField = useTemplateRef('contentField');
const editedQuickFactId = ref(null);

const toggle = () => {
  axios.put(props.data.url.toggle).then(() => {
    openState.value = !openState.value;
  });
};

const showCreateQuickFactModal = () => {
  createQuickFactModalShown.value = true;
  form.content = '';

  nextTick().then(() => contentField.value.focus());
};

const showEditQuickFactModal = (quickFact) => {
  editedQuickFactId.value = quickFact.id;
  form.content = quickFact.content;

  nextTick().then(() => contentField.value.focus());
};

const get = (template) => {
  loading.value = true;

  axios.get(template.url.show).then((response) => {
    localTemplate.value = response.data.data.template;
    localQuickFacts.value = response.data.data.quick_facts;
    loading.value = false;
  });
};

const store = () => {
  loadingState.value = 'loading';

  axios
    .post(localTemplate.value.url.store, form)
    .then((response) => {
      loadingState.value = '';
      createQuickFactModalShown.value = false;
      localQuickFacts.value.push(response.data.data);
    })
    .catch(() => {
      loadingState.value = '';
    });
};

const update = (quickFact) => {
  loadingState.value = 'loading';

  axios
    .put(quickFact.url.update, form)
    .then((response) => {
      loadingState.value = '';
      editedQuickFactId.value = 0;
      localQuickFacts.value[localQuickFacts.value.findIndex((x) => x.id === quickFact.id)] = response.data.data;
    })
    .catch(() => {
      loadingState.value = '';
    });
};

const destroy = (quickFact) => {
  axios.delete(quickFact.url.destroy).then(() => {
    var id = localQuickFacts.value.findIndex((x) => x.id === quickFact.id);
    localQuickFacts.value.splice(id, 1);
  });
};
</script>

<template>
  <div class="mb-8 rounded-lg border border-gray-200 bg-gray-50 p-3 shadow-xs dark:border-gray-700 dark:bg-gray-900">
    <div @click="toggle()" class="flex cursor-pointer items-center justify-between" :class="openState ? ' mb-4' : ''">
      <div class="flex items-center gap-2">
        <Lightbulb class="h-4 w-4 text-gray-600" />
        <p class="text-sm font-bold">{{ $t('Quick facts') }}</p>
      </div>

      <!-- chevrons -->
      <div>
        <ChevronIcon v-if="openState" :type="'down'" />

        <ChevronIcon v-if="!openState" :type="'up'" />
      </div>
    </div>

    <div v-if="openState">
      <!-- templates -->
      <div class="mb-4 flex">
        <ul class="list">
          <li v-for="template in data.templates" :key="template.id" class="me-2 inline">
            <span
              @click="get(template)"
              :class="
                localTemplate.id === template.id
                  ? 'rounded-xs border border-gray-200 bg-white font-semibold dark:bg-gray-800'
                  : ''
              "
              class="cursor-pointer px-2 py-1 text-sm">
              {{ template.label }}
            </span>
          </li>
        </ul>
      </div>

      <!-- content -->
      <ul v-if="localQuickFacts.length > 0 && !loading">
        <li
          v-for="quickFact in localQuickFacts"
          :key="quickFact.id"
          class="border-b border-dotted border-gray-300 px-2 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">
          <!-- normal mode -->
          <div v-if="editedQuickFactId !== quickFact.id" class="flex items-center justify-between">
            <div class="flex items-center">
              <ValideIcon />

              <span class="grow">{{ quickFact.content }}</span>
            </div>

            <hover-menu
              :show-edit="true"
              :show-delete="true"
              @edit="showEditQuickFactModal(quickFact)"
              @delete="destroy(quickFact)" />
          </div>

          <!-- edit mode -->
          <form
            v-if="editedQuickFactId === quickFact.id"
            class="mt-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
            @submit.prevent="update(quickFact)">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <errors :errors="form.errors" />

              <text-input
                ref="contentField"
                v-model="form.content"
                :label="$t('Content')"
                :type="'text'"
                :autofocus="true"
                :input-class="'block w-full'"
                :required="true"
                :autocomplete="false"
                :maxlength="255"
                @esc-key-pressed="editedQuickFactId = 0" />
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedQuickFactId = 0" />
              <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
            </div>
          </form>
        </li>
        <li v-if="!createQuickFactModalShown" class="ms-2 mt-1">
          <span @click="showCreateQuickFactModal()" class="cursor-pointer text-blue-500 hover:underline">{{
            $t('+ add another')
          }}</span>
        </li>
      </ul>

      <!-- blank state -->
      <div v-if="localQuickFacts.length === 0 && !loading && !createQuickFactModalShown" class="mb-2">
        <p class="px-5 pb-2 text-center">
          {{ $t('There are no quick facts here yet.') }}

          <span @click="showCreateQuickFactModal" class="cursor-pointer text-blue-500 hover:underline">{{
            $t('Add one now')
          }}</span>
        </p>
        <img src="/img/contact_blank_quick_facts.svg" :alt="$t('Activity feed')" class="mx-auto h-20 w-20" />
      </div>

      <!-- loading mode -->
      <div v-if="loading" class="px-20 py-5 text-center">
        <Loading />
      </div>

      <!-- modal to create a quick fact -->
      <form
        v-if="createQuickFactModalShown"
        class="mt-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
        @submit.prevent="store()">
        <div class="border-b border-gray-200 p-5 dark:border-gray-700">
          <errors :errors="form.errors" />

          <text-input
            ref="contentField"
            v-model="form.content"
            :label="$t('Content')"
            :type="'text'"
            :autofocus="true"
            :input-class="'block w-full'"
            :required="true"
            :autocomplete="false"
            :maxlength="255"
            @esc-key-pressed="createQuickFactModalShown = false" />
        </div>

        <div class="flex justify-between p-5">
          <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createQuickFactModalShown = false" />
          <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
        </div>
      </form>
    </div>
  </div>
</template>

<style lang="scss" scoped></style>
