<script setup>
import { ref, nextTick, useTemplateRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { flash } from '@/methods.js';
import { Tooltip as ATooltip } from 'ant-design-vue';
import Errors from '@/Shared/Form/Errors.vue';
import PrettyButton from '@/Shared/Form/PrettyButton.vue';
import TextInput from '@/Shared/Form/TextInput.vue';
import PrettySpan from '@/Shared/Form/PrettySpan.vue';
import HoverMenu from '@/Shared/HoverMenu.vue';
import { ChartSpline } from 'lucide-vue-next';

const props = defineProps({
  data: Object,
});

const form = useForm({
  label: '',
});

const createLifeMetricModalShown = ref(false);
const labelField = useTemplateRef('labelField');
const loadingState = ref('');
const localLifeMetrics = ref(props.data.data);
const editedLifeMetricId = ref(0);
const graphLifeMetricId = ref(0);

const showCreateLifeMetricModal = () => {
  createLifeMetricModalShown.value = true;
  form.label = '';

  nextTick().then(() => labelField.value.focus());
};

const showEditLifeMetricModal = (lifeMetric) => {
  editedLifeMetricId.value = lifeMetric.id;
  form.label = lifeMetric.label;
};

const showLifeMetricGraph = (lifeMetric) => {
  graphLifeMetricId.value = lifeMetric.id;
};

const toggleGraph = (lifeMetric) => {
  let id = localLifeMetrics.value.findIndex((x) => x.id === lifeMetric.id);
  localLifeMetrics.value[id].show_graph = !localLifeMetrics.value[id].show_graph;
};

const store = () => {
  loadingState.value = 'loading';

  axios
    .post(props.data.url.store, form)
    .then((response) => {
      flash(trans('The life metric has been created'), 'success');
      loadingState.value = '';
      createLifeMetricModalShown.value = false;
      localLifeMetrics.value.push(response.data.data);
    })
    .catch(() => {
      loadingState.value = '';
    });
};

const increment = (lifeMetric) => {
  loadingState.value = 'loading';

  axios
    .post(lifeMetric.url.store, form)
    .then((response) => {
      loadingState.value = '';
      localLifeMetrics.value[localLifeMetrics.value.findIndex((x) => x.id === lifeMetric.id)] = response.data.data;
      localLifeMetrics.value[localLifeMetrics.value.findIndex((x) => x.id === lifeMetric.id)].incremented = true;
    })
    .catch(() => {
      loadingState.value = '';
    });
};

const update = (lifeMetric) => {
  loadingState.value = 'loading';

  axios
    .put(lifeMetric.url.update, form)
    .then((response) => {
      flash(trans('The life metric has been updated'), 'success');
      loadingState.value = '';
      editedLifeMetricId.value = 0;
      localLifeMetrics.value[localLifeMetrics.value.findIndex((x) => x.id === lifeMetric.id)] = response.data.data;
    })
    .catch(() => {
      loadingState.value = '';
    });
};

const destroy = (lifeMetric) => {
  if (confirm(trans('Are you sure? This action cannot be undone.'))) {
    axios
      .delete(lifeMetric.url.destroy)
      .then(() => {
        flash(trans('The life metric has been deleted'), 'success');
        let id = localLifeMetrics.value.findIndex((x) => x.id === lifeMetric.id);
        localLifeMetrics.value.splice(id, 1);
      })
      .catch(() => {});
  }
};
</script>

<template>
  <div class="mb-10">
    <!-- title + cta -->
    <div class="mb-3 items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700 sm:flex">
      <div class="mb-2 sm:mb-0 flex items-center gap-2">
        <ChartSpline class="h-4 w-4" />

        <span class="font-semibold"> {{ $t('Life metrics') }} </span>
      </div>
      <pretty-button
        v-if="!createLifeMetricModalShown"
        :text="$t('Track a new metric')"
        :icon="'plus'"
        :class="'w-full sm:w-fit'"
        @click="showCreateLifeMetricModal" />
    </div>

    <!-- modal to create a quick fact -->
    <form
      v-if="createLifeMetricModalShown"
      class="mb-2 mt-2 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
      @submit.prevent="store()">
      <div class="border-b border-gray-200 p-5 dark:border-gray-700">
        <errors :errors="form.errors" />

        <text-input
          ref="labelField"
          v-model="form.label"
          :label="$t('Name')"
          :type="'text'"
          :autofocus="true"
          :input-class="'block w-full'"
          :required="true"
          :autocomplete="false"
          :placeholder="$t('Watch Netflix every day')"
          :maxlength="255"
          @esc-key-pressed="createLifeMetricModalShown = false" />
      </div>

      <div class="flex justify-between p-5">
        <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="createLifeMetricModalShown = false" />
        <pretty-button :text="$t('Save')" :state="loadingState" :icon="'plus'" :class="'save'" />
      </div>
    </form>

    <div>
      <!-- list of life metrics -->
      <ul
        v-if="localLifeMetrics.length > 0"
        class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <li
          v-for="lifeMetric in localLifeMetrics"
          :key="lifeMetric.id"
          class="item-list border-b border-gray-200 hover:bg-slate-50 dark:border-gray-700 dark:bg-slate-900 dark:hover:bg-slate-800">
          <div v-if="editedLifeMetricId !== lifeMetric.id" class="flex items-center justify-between p-3">
            <div class="me-8 flex w-full items-center justify-between">
              <div>
                <p class="mb-1 text-lg font-semibold">{{ lifeMetric.label }}</p>
                <ul @click="toggleGraph(lifeMetric)">
                  <li @click="showLifeMetricGraph(lifeMetric)" class="text-sm text-gray-600">
                    {{ $t('Total:') }}

                    <a-tooltip placement="bottomLeft" :title="$t('Events this week')" arrow-point-at-center>
                      <span
                        class="cursor-pointer whitespace-nowrap rounded-lg bg-slate-100 px-2 py-0.5 text-sm text-slate-400"
                        >{{ lifeMetric.stats.weekly_events }}</span
                      >
                    </a-tooltip>
                    <span class="mx-1 text-gray-400">/</span>
                    <a-tooltip placement="bottomLeft" :title="$t('Events this month')" arrow-point-at-center>
                      <span
                        class="cursor-pointer whitespace-nowrap rounded-lg bg-yellow-100 px-2 py-0.5 text-sm text-slate-400"
                        >{{ lifeMetric.stats.monthly_events }}</span
                      >
                    </a-tooltip>
                    <span class="mx-1 text-gray-400">/</span>
                    <a-tooltip placement="bottomLeft" :title="$t('Events this year')" arrow-point-at-center>
                      <span
                        class="cursor-pointer whitespace-nowrap rounded-lg bg-green-100 px-2 py-0.5 text-sm text-slate-400"
                        >{{ lifeMetric.stats.yearly_events }}</span
                      >
                    </a-tooltip>
                  </li>
                </ul>
              </div>

              <pretty-button
                v-if="!lifeMetric.incremented"
                :text="'+ 1'"
                :class="'w-full px-8 py-4 sm:w-fit'"
                @click="increment(lifeMetric)" />
              <span v-else class="w-full px-3 py-4 text-xl sm:w-fit">🤭</span>
            </div>

            <!-- menu -->
            <hover-menu
              :show-edit="true"
              :show-delete="true"
              @edit="showEditLifeMetricModal(lifeMetric)"
              @delete="destroy(lifeMetric)" />
          </div>

          <!-- graph -->
          <div
            v-if="editedLifeMetricId !== lifeMetric.id && lifeMetric.show_graph"
            class="m-3 mb-2 rounded-lg border border-gray-200">
            <table class="charts-css column show-labels show-primary-axis h-72">
              <tbody>
                <tr v-for="month in lifeMetric.months" :key="month.id">
                  <td :style="'--size: calc(' + month.events + '/' + lifeMetric.max_number_of_events">
                    {{ month.friendly_name }}

                    <span class="tooltip"> {{ month.events }} events </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- edit modal -->
          <form
            v-if="editedLifeMetricId === lifeMetric.id"
            class="bg-white dark:border-gray-700 dark:bg-gray-900"
            @submit.prevent="update(lifeMetric)">
            <div class="border-b border-gray-200 p-5 dark:border-gray-700">
              <errors :errors="form.errors" />

              <text-input
                ref="labelField"
                v-model="form.label"
                :label="$t('Name')"
                :type="'text'"
                :autofocus="true"
                :input-class="'block w-full'"
                :required="true"
                :autocomplete="false"
                :maxlength="255"
                @esc-key-pressed="editedLifeMetricId = 0" />
            </div>

            <div class="flex justify-between p-5">
              <pretty-span :text="$t('Cancel')" :class="'me-3'" @click="editedLifeMetricId = 0" />
              <pretty-button :text="$t('Save')" :state="loadingState" :icon="'check'" :class="'save'" />
            </div>
          </form>
        </li>
      </ul>

      <!-- blank state -->
      <div
        v-if="localLifeMetrics.length === 0"
        class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <img src="/img/vault_life_metrics_blank.svg" :alt="$t('Life events')" class="mx-auto mt-4 h-20 w-20" />
        <p class="px-5 pb-5 pt-2 text-center">
          {{ $t('Life metrics let you track metrics that are important to you.') }}
        </p>
      </div>
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
