<script setup>
import { onMounted, ref, watch, nextTick, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { DatePicker } from 'v-calendar';
import 'v-calendar/style.css';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import ContactSelector from '@/Shared/Form/ContactSelector.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import TextArea from '@/Shared/Form/TextArea.vue';
import Avatar from '@/Shared/Avatar.vue';
import ArrowIcon from '@/Shared/Icons/ArrowIcon.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
  openModal: Boolean,
  createTimelineEvent: Boolean,
  timelineEvent: Object,
  lifeEvent: Object,
});

const emit = defineEmits(['closeModal', 'timelineEventCreated', 'lifeEventCreated']);

const form = useForm({
  lifeEventTypeId: 0,
  label: null,
  started_at: null,
  participants: [],
  summary: null,
  description: null,
  distance: 0,
  distance_unit: 'km',
});

const loadingState = ref(false);
const selectedLifeEventCategory = ref([]);
const selectedLifeEventType = ref(null);
const editDate = ref(false);
const modalShown = ref(false);
const addSummaryFieldShown = ref(false);
const addDescriptionFieldShown = ref(false);
const addDistanceFieldShown = ref(false);
const summaryField = useTemplateRef('summaryField');
const descriptionField = useTemplateRef('descriptionField');
const distanceField = useTemplateRef('distanceField');
const masks = ref({
  modelValue: 'YYYY-MM-DD',
});

watch(
  () => props.openModal,
  (value) => {
    modalShown.value = value;
  },
);

onMounted(() => {
  resetModal();
  if (props.lifeEvent) {
    form.label = props.lifeEvent.label;
    form.started_at = props.lifeEvent.started_at;
    form.lifeEventTypeId = props.lifeEvent.life_event_type.id;
    selectedLifeEventCategory.value = props.lifeEvent.life_event_type.category;
    selectedLifeEventType.value = props.lifeEvent.life_event_type;
    if (props.lifeEvent.summary) {
      form.summary = props.lifeEvent.summary;
      addSummaryFieldShown.value = true;
    }
    if (props.lifeEvent.description) {
      form.description = props.lifeEvent.description;
      addDescriptionFieldShown.value = true;
    }
    if (props.lifeEvent.distance) {
      form.distance = props.lifeEvent.distance;
      form.distance_unit = props.lifeEvent.distance_unit;
      addDistanceFieldShown.value = true;
    }
    form.participants = props.lifeEvent.participants;
    selectedLifeEventType.value = props.lifeEvent.life_event_type;
  }
});

const resetModal = () => {
  modalShown.value = props.openModal;
  selectedLifeEventCategory.value = props.data.life_event_categories[0];
  form.started_at = props.data.current_date;

  form.summary = null;
  form.description = null;
  form.distance = 0;
  addSummaryFieldShown.value = false;
  addDescriptionFieldShown.value = false;
  addDistanceFieldShown.value = false;
};

const loadTypes = (category) => {
  var id = props.data.life_event_categories.findIndex((x) => x.id === category.id);
  selectedLifeEventCategory.value = props.data.life_event_categories[id];
};

const chooseType = (type) => {
  selectedLifeEventType.value = type;
  form.lifeEventTypeId = type.id;
};

const resetType = () => {
  selectedLifeEventCategory.value = props.data.life_event_categories[0];
  selectedLifeEventType.value = null;
};

const showAddSummaryField = () => {
  form.summary = null;
  addSummaryFieldShown.value = true;

  nextTick().then(() => summaryField.value.focus());
};

const showAddDescriptionField = () => {
  form.description = null;
  addDescriptionFieldShown.value = true;

  nextTick().then(() => descriptionField.value.focus());
};

const showAddDistanceField = () => {
  form.distance = null;
  addDistanceFieldShown.value = true;

  nextTick().then(() => distanceField.value.focus());
};

const store = () => {
  loadingState.value = 'loading';

  // we either called the Create life event modal from inside an existing timeline
  // event, or from a new timeline event
  // this changes the url we post to as we need to pass the right info back to
  // the parent (ie. if it needs to refresh a specific timeline event, or the entire
  // timeline)
  let request = {
    method: 'post',
    data: form,
  };
  if (props.lifeEvent) {
    request.url = props.lifeEvent.url.edit;
    request.method = 'put';
  } else {
    request.url = props.createTimelineEvent ? props.data.url.store : props.timelineEvent.url.store;
  }

  axios
    .request(request)
    .then((response) => {
      loadingState.value = '';
      nextTick().then(() => emit('closeModal'));

      if (props.createTimelineEvent) {
        emit('timelineEventCreated', response.data.data);
      } else {
        emit('lifeEventCreated', response.data.data);
      }

      selectedLifeEventType.value = null;
      resetModal();
    })
    .catch(() => {
      loadingState.value = '';
    });
};
</script>

<template>
  <div>
    <form
      v-if="modalShown"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="store()">
      <!-- choose life event categories/types -->
      <div v-if="!selectedLifeEventType" class="border-b border-gray-200 dark:border-gray-700">
        <div class="grid-skeleton grid grid-cols-2 justify-center gap-2 p-3">
          <!-- choose a life event type -->
          <div>
            <p class="mb-1 text-xs font-semibold">{{ $t('Categories') }}</p>
            <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <li
                @click="loadTypes(category)"
                v-for="category in data.life_event_categories"
                :key="category.id"
                class="item-list flex cursor-pointer border-b border-gray-200 px-3 py-1 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                <div
                  class="flex w-full justify-between"
                  :class="category.id === selectedLifeEventCategory.id ? 'font-bold' : ''">
                  <!-- label category -->
                  <div>{{ category.label }}</div>

                  <!-- arrow -->
                  <span v-if="category.id === selectedLifeEventCategory.id">
                    <ArrowIcon />
                  </span>
                </div>
              </li>
            </ul>
          </div>

          <!-- list of life event types -->
          <div>
            <p class="mb-1 text-xs font-semibold">{{ $t('Types') }}</p>
            <ul class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
              <li
                v-for="lifeEventType in selectedLifeEventCategory.life_event_types"
                :key="lifeEventType.id"
                @click="chooseType(lifeEventType)"
                class="item-list flex cursor-pointer justify-between border-b border-gray-200 px-3 py-1 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
                <span>{{ lifeEventType.label }}</span>
                <span class="text-sm text-blue-500 hover:underline">{{ $t('Choose') }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- type has been selected -->
      <div v-else class="flex items-center justify-between border-b border-gray-200 p-3 dark:border-gray-700">
        <div>
          <span class="text-sm">{{ $t('Chosen type:') }}</span>
          <span class="rounded-xs border bg-white px-2 py-1 font-mono text-sm dark:bg-gray-800">
            {{ selectedLifeEventCategory.label }}
          </span>
          >
          <span class="rounded-xs border bg-white px-2 py-1 font-mono text-sm dark:bg-gray-800">
            {{ selectedLifeEventType.label }}
          </span>
        </div>

        <p @click="resetType()" class="cursor-pointer text-sm text-blue-500 hover:underline">{{ $t('Change') }}</p>
      </div>

      <!-- date of the event -->
      <div v-if="selectedLifeEventType" class="border-b border-gray-200 p-3 dark:border-gray-700">
        <!-- default date -->
        <div v-if="!editDate" class="flex items-center justify-between">
          <div>
            <span class="text-sm">{{ $t('Date of the event:') }}</span>
            {{ lifeEvent ? lifeEvent.happened_at : data.current_date_human_format }}
          </div>

          <p @click="editDate = true" class="cursor-pointer text-sm text-blue-500 hover:underline">
            {{ $t('Change') }}
          </p>
        </div>

        <!-- customize date -->
        <div v-if="editDate">
          <p class="mb-2 block text-sm dark:text-gray-100">{{ $t('Date of the event') }}</p>
          <DatePicker
            v-model.string="form.started_at"
            :timezone="'UTC'"
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

      <!-- participants -->
      <div v-if="selectedLifeEventType" class="border-b border-gray-200 p-3 dark:border-gray-700">
        <p class="mb-2 block text-sm dark:text-gray-100">{{ $t('Participants') }}</p>

        <!-- current contact -->
        <div class="mb-4 flex items-center">
          <avatar :data="data.contact.avatar" :class="'me-2 h-5 w-5'" />

          <span>{{ data.contact.name }}</span>
        </div>

        <!-- all other participants -->
        <contact-selector
          v-model="form.participants"
          :search-url="layoutData.vault.url.search_contacts_only"
          :most-consulted-contacts-url="layoutData.vault.url.get_most_consulted_contacts"
          :display-most-consulted-contacts="true"
          :add-multiple-contacts="true"
          :required="true"
          :class="'flex-1 border-gray-200 dark:border-gray-700'" />
      </div>

      <!-- summary -->
      <div
        v-if="selectedLifeEventType && addSummaryFieldShown"
        class="border-b border-gray-200 p-3 dark:border-gray-700">
        <text-input
          ref="summaryField"
          v-model="form.summary"
          :label="$t('Summary')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="false"
          :autocomplete="false"
          :maxlength="255"
          @esc-key-pressed="addSummaryFieldShown = false" />
      </div>

      <!-- description -->
      <div
        v-show="selectedLifeEventType && addDescriptionFieldShown"
        class="border-b border-gray-200 p-3 dark:border-gray-700">
        <TextArea
          ref="descriptionField"
          v-model="form.description"
          :label="$t('Description')"
          @esc-key-pressed="addDescriptionFieldShown = false"
          :maxlength="65535"
          :textarea-class="'block w-full'" />
      </div>

      <!-- description -->
      <div
        v-if="selectedLifeEventType && addDistanceFieldShown"
        class="flex items-center border-b border-gray-200 pb-1 pe-3 ps-3 pt-3 dark:border-gray-700">
        <text-input
          ref="distanceField"
          v-model="form.distance"
          :label="$t('Distance')"
          :type="'number'"
          :autofocus="true"
          :input-class="'me-2'"
          :required="false"
          :autocomplete="false"
          :help="$t('Enter a number from 0 to 100000. No decimals.')"
          :min="0"
          :max="100000"
          @esc-key-pressed="addDistanceFieldShown = false" />

        <ul>
          <li class="me-5 inline-block">
            <div class="flex items-center">
              <input
                id="km"
                v-model="form.distance_unit"
                value="km"
                name="distance_unit"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
              <label for="km" class="ms-1 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t('km') }}
              </label>
            </div>
          </li>

          <li class="inline-block">
            <div class="flex items-center">
              <input
                id="miles"
                v-model="form.distance_unit"
                value="miles"
                name="distance_unit"
                type="radio"
                class="h-4 w-4 border-gray-300 text-sky-500 dark:border-gray-700" />
              <label for="miles" class="ms-1 block cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t('miles') }}
              </label>
            </div>
          </li>
        </ul>
      </div>

      <!-- options -->
      <div v-if="selectedLifeEventType" class="flex flex-wrap border-b border-gray-200 p-3 dark:border-gray-700">
        <!-- summary -->
        <div v-if="!addSummaryFieldShown">
          <span
            class="mb-2 me-2 cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-sm hover:bg-slate-300 dark:border-gray-500 dark:bg-slate-800 dark:text-gray-50 dark:hover:bg-slate-700"
            @click="showAddSummaryField"
            >{{ $t('+ add summary') }}
          </span>
        </div>

        <!-- description -->
        <div v-if="!addDescriptionFieldShown">
          <span
            class="mb-2 me-2 cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-sm hover:bg-slate-300 dark:border-gray-500 dark:bg-slate-800 dark:text-gray-50 dark:hover:bg-slate-700"
            @click="showAddDescriptionField"
            >{{ $t('+ add description') }}
          </span>
        </div>

        <!-- distance -->
        <div v-if="!addDistanceFieldShown">
          <span
            class="mb-2 me-2 cursor-pointer rounded-lg border bg-slate-200 px-1 py-1 text-sm hover:bg-slate-300 dark:border-gray-500 dark:bg-slate-800 dark:text-gray-50 dark:hover:bg-slate-700"
            @click="showAddDistanceField"
            >{{ $t('+ add distance') }}
          </span>
        </div>
      </div>
      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="$emit('closeModal')" />
        <pretty-button
          v-if="selectedLifeEventType"
          :text="$t('Save')"
          :state="loadingState"
          :icon="'plus'"
          :class="'save'" />
      </div>
    </form>
  </div>
</template>

<style lang="scss" scoped>
.grid-skeleton {
  grid-template-columns: 1fr 2fr;
}

@media (max-width: 480px) {
  .grid-skeleton {
    grid-template-columns: 1fr;
  }
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
