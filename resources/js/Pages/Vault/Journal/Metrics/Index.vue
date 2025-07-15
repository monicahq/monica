<script setup>
import { ref, nextTick, useTemplateRef } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import Layout from '@/Layouts/Layout.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import Errors from '@/Shared/Form/Errors.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
});

const localMetrics = ref(props.data.journalMetrics);
const loadingState = ref('');
const newJournalMetric = useTemplateRef('newJournalMetric');
const createJournalMetricModalShown = ref(false);

const form = useForm({
  label: '',
});

const showJournalMetricModal = () => {
  form.label = '';
  createJournalMetricModalShown.value = true;

  nextTick().then(() => newJournalMetric.value.focus());
};

const submit = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form)
    .then((response) => {
      createJournalMetricModalShown.value = false;
      loadingState.value = '';
      localMetrics.value.unshift(response.data.data);
    })
    .catch(() => {
      loadingState.value = '';
    });
};

const destroy = (metric) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(metric.url.destroy)
      .then(() => {
        var id = localMetrics.value.findIndex((x) => x.id === metric.id);
        localMetrics.value.splice(id, 1);
      })
      .catch((error) => {
        loadingState.value = null;
        form.errors = error.response.data;
      });
  }
};
</script>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white dark:bg-gray-900 sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="me-2 inline text-gray-600 dark:text-gray-400">
              {{ $t('You are here:') }}
            </li>
            <li class="me-2 inline">
              <Link :href="layoutData.vault.url.journals" class="text-blue-500 hover:underline">
                {{ $t('Journals') }}
              </Link>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="relative me-2 inline">
              <Link :href="data.journal.url.show" class="text-blue-500 hover:underline">{{ data.journal.name }}</Link>
            </li>
            <li class="relative me-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">{{ $t('Journal metrics') }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-4xl px-2 py-2 sm:px-6 sm:py-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 flex items-center justify-between">
          <h3>
            <span class="me-1"> 📊 </span>
            {{ $t('All journal metrics in :name', { name: data.journal.name }) }}
          </h3>

          <pretty-button
            v-if="!createJournalMetricModalShown"
            @click="showJournalMetricModal"
            :text="$t('Create a journal metric')"
            :icon="'plus'" />
        </div>

        <!-- modal to create a journal metric -->
        <form
          v-if="createJournalMetricModalShown"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
          @submit.prevent="submit()">
          <div class="border-b border-gray-200 p-5 dark:border-gray-700">
            <errors :errors="form.errors" />

            <text-input
              ref="newJournalMetric"
              v-model="form.label"
              :label="$t('Name')"
              :type="'text'"
              :autofocus="true"
              :input-class="'block w-full'"
              :required="true"
              :autocomplete="false"
              :maxlength="255"
              @esc-key-pressed="createJournalMetricModalShown = false" />
          </div>

          <div class="flex justify-between p-5">
            <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createJournalMetricModalShown = false" />
            <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
          </div>
        </form>

        <div v-if="localMetrics.length !== 0">
          <ul class="metric-list mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <li
              v-for="metric in localMetrics"
              :key="metric.id"
              class="flex items-center justify-between border-b border-gray-200 px-5 py-4 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
              <span>{{ metric.label }}</span>

              <!-- actions -->
              <ul class="text-sm">
                <li class="inline cursor-pointer text-red-500 hover:text-red-900" @click="destroy(metric)">
                  {{ $t('Delete') }}
                </li>
              </ul>
            </li>
          </ul>
        </div>

        <!-- blank state -->
        <div
          v-if="localMetrics.length === 0"
          class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
          <img src="/img/journal_blank_index.svg" :alt="$t('Journal')" class="mx-auto mt-4 h-44 w-44" />
          <p class="px-5 pb-5 pt-2 text-center">
            {{ $t('Journal metrics let you track data accross all your journal entries.') }}
          </p>
        </div>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.metric-list {
  li:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  li:last-child {
    border-bottom: 0;
  }

  li:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>
