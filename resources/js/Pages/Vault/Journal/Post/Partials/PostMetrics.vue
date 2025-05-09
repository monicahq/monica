<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import Errors from '@/Shared/Form/Errors.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';

const props = defineProps({
  data: Object,
});

const form = useForm({
  journal_metric_id: '',
  label: '',
  value: '',
});

const loadingState = ref(false);
const localJournalMetrics = ref(props.data.journal_metrics);
const journalMetricModal = ref(0);
const addModalShown = ref(false);
const editModeJournalMetricId = ref(0);

const showAddMetricModal = (journalMetric) => {
  journalMetricModal.value = journalMetric.id;
  addModalShown.value = true;
  form.label = '';
  form.value = '';
};

const showEditMetricModal = (journalMetric) => {
  editModeJournalMetricId.value = journalMetric.id;
};

const store = (journalMetric) => {
  loadingState.value = 'loading';
  form.journal_metric_id = journalMetric.id;

  axios
    .post(journalMetric.url.store, form)
    .then((response) => {
      loadingState.value = '';
      localJournalMetrics.value[
        localJournalMetrics.value.findIndex((x) => x.id === journalMetric.id)
      ].post_metrics.push(response.data.data);
      addModalShown.value = false;
    })
    .catch(() => {
      loadingState.value = '';
    });
};

const destroy = (journalMetric, postMetric) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios.delete(postMetric.url.destroy).then(() => {
      var id = localJournalMetrics.value.findIndex((x) => x.id === journalMetric.id);
      var postMetricId = localJournalMetrics.value[id].post_metrics.findIndex((x) => x.id === postMetric.id);
      localJournalMetrics.value[id].post_metrics.splice(postMetricId, 1);
    });
  }
};
</script>

<template>
  <div class="mb-8">
    <p class="mb-2 flex items-center justify-between font-bold">
      <span>{{ $t('Post metrics') }}</span>
    </p>

    <!-- journal metrics -->
    <div v-for="journalMetric in localJournalMetrics" :key="journalMetric.id" class="mb-3">
      <div class="mb-1 font-semibold">{{ journalMetric.label }}</div>
      <ul
        v-if="journalMetric.post_metrics.length > 0"
        class="mb-2 rounded-xs border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="postMetric in journalMetric.post_metrics"
          :key="postMetric.id"
          class="item-list flex items-center justify-between border-b border-gray-200 px-3 py-1 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <span class="italic">{{ postMetric.label }}</span>

          <div class="flex items-center">
            <span class="font-mono text-sm">{{ postMetric.value }}</span>
            <span
              @click="destroy(journalMetric, postMetric)"
              v-if="editModeJournalMetricId === journalMetric.id"
              class="ms-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-4 w-4 cursor-pointer text-red-500 hover:text-red-900">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </span>
          </div>
        </li>
      </ul>
      <ul>
        <li
          @click="showAddMetricModal(journalMetric)"
          v-if="!addModalShown && editModeJournalMetricId !== journalMetric.id"
          class="mb-6 me-3 inline cursor-pointer text-sm text-blue-500 hover:underline">
          {{ $t('add a new metric') }}
        </li>
        <li
          @click="showEditMetricModal(journalMetric)"
          v-if="!addModalShown && journalMetric.post_metrics.length > 0 && editModeJournalMetricId !== journalMetric.id"
          class="mb-6 inline cursor-pointer text-sm">
          <span class="text-blue-500 hover:underline">{{ $t('edit') }}</span>
        </li>
        <li
          @click="editModeJournalMetricId = 0"
          v-if="editModeJournalMetricId === journalMetric.id"
          class="mb-6 inline cursor-pointer text-sm">
          <span class="text-blue-500 hover:underline">{{ $t('close edit mode') }}</span>
        </li>
      </ul>

      <!-- modal to add a new post metric -->
      <div
        v-if="addModalShown && journalMetric.id === journalMetricModal"
        class="mb-6 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
        <form @submit.prevent="store(journalMetric)">
          <div class="border-b border-gray-200 p-2 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              v-model="form.value"
              :autofocus="true"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="true"
              :type="'number'"
              :min="0"
              :max="1000000"
              :label="$t('Numerical value')"
              @esc-key-pressed="addModalShown = false" />

            <text-input
              v-model="form.label"
              :class="'mb-5'"
              :input-class="'block w-full'"
              :required="false"
              :maxlength="255"
              :label="$t('More details')"
              @esc-key-pressed="addModalShown = false" />
          </div>

          <div class="flex justify-between p-2">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="addModalShown = false" />
            <pretty-button
              :href="'data.url.vault.create'"
              :text="$t('Save')"
              :state="loadingState"
              :icon="'check'"
              :class="'save'" />
          </div>
        </form>
      </div>
    </div>

    <!-- blank state -->
    <p v-if="localJournalMetrics.length <= 0" class="text-sm text-gray-600 dark:text-gray-400">
      {{ $t('There are no journal metrics.') }}
    </p>
  </div>
</template>

<style lang="scss" scoped>
.item-list {
  &:hover:first-child {
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
  }
}
</style>
