<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Tooltip as ATooltip } from 'ant-design-vue';
import Layout from '@/Layouts/Layout.vue';
import ContactCard from '@/Shared/ContactCard.vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
defineProps({
  layoutData: Object,
  data: Object,
});

const loadedDay = ref([]);
const dayDetailsLoaded = ref(false);

const get = (day) => {
  if (day.is_in_month === false) {
    return;
  }

  axios.get(day.url.show).then((response) => {
    dayDetailsLoaded.value = true;
    loadedDay.value = response.data.data;
  });
};
</script>

<template>
  <layout title="Calendar" :inside-vault="true" :layout-data="layoutData">
    <main class="relative sm:mt-24">
      <div class="max-w-8xl mx-auto py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-0">
            <!-- month browser -->
            <div class="mb-4 flex items-center justify-between">
              <!-- month name -->
              <p class="text-lg font-bold">{{ data.current_month }}</p>

              <!-- month next/previous -->
              <div class="flex justify-center">
                <div class="inline-flex rounded-md shadow-xs">
                  <Link
                    :href="data.url.previous"
                    class="flex items-center gap-2 rounded-s-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-blue-500">
                    <ChevronLeft class="h-4 w-4" />

                    {{ data.previous_month }}
                  </Link>

                  <Link
                    :href="data.url.next"
                    class="flex items-center gap-2 rounded-e-md border-y border-e border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-blue-500">
                    {{ data.next_month }}

                    <ChevronRight class="h-4 w-4" />
                  </Link>
                </div>
              </div>
            </div>

            <!-- days -->
            <div
              class="grid grid-cols-7 rounded-t-lg border-x border-t border-gray-200 last:border-b dark:border-gray-700">
              <div class="border-e border-gray-200 p-2 text-center text-xs dark:border-gray-700">
                {{ $t('Monday') }}
              </div>
              <div class="border-e border-gray-200 p-2 text-center text-xs dark:border-gray-700">
                {{ $t('Tuesday') }}
              </div>
              <div class="border-e border-gray-200 p-2 text-center text-xs dark:border-gray-700">
                {{ $t('Wednesday') }}
              </div>
              <div class="border-e border-gray-200 p-2 text-center text-xs dark:border-gray-700">
                {{ $t('Thursday') }}
              </div>
              <div class="border-e border-gray-200 p-2 text-center text-xs dark:border-gray-700">
                {{ $t('Friday') }}
              </div>
              <div class="border-e border-gray-200 p-2 text-center text-xs dark:border-gray-700">
                {{ $t('Saturday') }}
              </div>
              <div class="p-2 text-center text-xs">{{ $t('Sunday') }}</div>
            </div>

            <!-- actual calendar -->
            <div
              v-for="week in data.weeks"
              :key="week.id"
              class="grid grid-cols-7 border-x border-t border-gray-200 last:rounded-b-lg last:border-b dark:border-gray-700">
              <div
                v-for="day in week"
                :key="day.id"
                @click="get(day)"
                class="h-32 border-e border-gray-200 p-2 last:border-e-0 dark:border-gray-700"
                :class="day.is_in_month ? 'cursor-pointer' : 'bg-slate-50 dark:bg-slate-900'">
                <!-- date of the day -->
                <div class="flex items-center justify-between">
                  <span
                    class="mb-1 inline-block p-1 text-xs"
                    :class="day.is_today ? 'rounded-lg bg-slate-200 dark:bg-slate-900' : ''"
                    >{{ day.date }}</span
                  >

                  <!-- mood for the day -->
                  <div class="flex">
                    <div v-for="mood in day.mood_events" :key="mood.id">
                      <a-tooltip placement="topLeft" :title="mood.mood_tracking_parameter.label" arrow-point-at-center>
                        <div
                          class="me-2 inline-block h-4 w-4 rounded-full"
                          :class="mood.mood_tracking_parameter.hex_color" />
                      </a-tooltip>
                    </div>
                  </div>
                </div>

                <!-- important dates -->
                <div v-if="day.important_dates?.length > 0" class="mb-1 text-xs text-gray-600">
                  {{ $t('Important dates') }}
                </div>
                <div v-if="day.important_dates?.length > 0" class="flex">
                  <div v-for="date in day.important_dates" :key="date.id">
                    <contact-card
                      :contact="date.contact"
                      :avatar-classes="'h-5 w-5 rounded-full me-2'"
                      :display-name="false" />
                  </div>
                </div>

                <!-- posts of journal -->
                <div v-if="day.posts?.length > 0" class="mb-1 text-xs text-gray-600">
                  {{ $tChoice(':count post|:count posts', day.posts.length, { count: day.posts.length }) }}
                </div>
              </div>
            </div>
          </div>

          <!-- right part: detail of a day -->
          <div v-if="dayDetailsLoaded" class="rounded-lg border border-gray-200 p-3 dark:border-gray-700 sm:p-0">
            <!-- day name -->
            <div class="border-b border-gray-200 p-2 text-center text-sm font-semibold dark:border-gray-700">
              {{ loadedDay.day }}
            </div>

            <!-- mood -->
            <div v-if="loadedDay.mood_events.length > 0" class="border-b border-gray-200 dark:border-gray-700">
              <h2
                class="border-b border-gray-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-gray-600 dark:border-gray-700 dark:bg-slate-900">
                {{ $t('Your mood that day') }}
              </h2>
              <ul class="p-3">
                <li v-for="mood in loadedDay.mood_events" :key="mood.id" class="mb-2">
                  <!-- mood tracking parameter -->
                  <div class="flex items-center">
                    <div
                      class="me-2 inline-block h-4 w-4 rounded-full"
                      :class="mood.mood_tracking_parameter.hex_color" />
                    <span>{{ mood.mood_tracking_parameter.label }}</span>
                  </div>

                  <!-- optional information -->
                  <div
                    v-if="mood.number_of_hours_slept || mood.note"
                    class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <!-- number of hours slept -->
                    <div v-if="mood.number_of_hours_slept" class="mb-1 flex items-center text-sm text-gray-600">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="me-1 h-4 w-4 text-gray-400">
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                      </svg>

                      {{
                        $tChoice(':count hour slept|:count hours slept', mood.number_of_hours_slept, {
                          count: mood.number_of_hours_slept,
                        })
                      }}
                    </div>

                    <!-- note -->
                    <div v-if="mood.note" class="flex items-center text-sm text-gray-600">
                      {{ mood.note }}
                    </div>
                  </div>
                </li>
              </ul>
            </div>

            <!-- important dates -->
            <div v-if="loadedDay.important_dates.length > 0">
              <h2
                class="border-b border-gray-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-gray-600 dark:border-gray-700 dark:bg-slate-900">
                {{ $t('Important dates') }}
              </h2>
              <ul class="p-3">
                <li
                  v-for="importantDate in loadedDay.important_dates"
                  :key="importantDate.id"
                  class="mb-1 flex justify-between">
                  <span>{{ importantDate.label }}</span>
                  <span
                    ><contact-card
                      :contact="importantDate.contact"
                      :avatar-classes="'h-5 w-5 rounded-full me-2'"
                      :display-name="false"
                  /></span>
                </li>
              </ul>
            </div>

            <!-- journal entries -->
            <div v-if="loadedDay.posts.length > 0" class="border-b border-gray-200 dark:border-gray-700">
              <h2
                class="border-b border-gray-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-gray-600 dark:border-gray-700 dark:bg-slate-900">
                {{ $t('Posts in your journals') }}
              </h2>
              <ul class="p-3">
                <li v-for="post in loadedDay.posts" :key="post.id" class="mb-2">
                  <Link :href="post.url.show" class="text-sm text-blue-500 hover:underline">{{ post.title }}</Link>
                </li>
              </ul>
            </div>

            <!-- case of no data in the day -->
            <div
              v-if="
                loadedDay.mood_events.length === 0 &&
                loadedDay.important_dates.length === 0 &&
                loadedDay.posts.length === 0
              "
              class="flex items-center justify-center">
              <p class="mt-4 px-5 pb-5 pt-2 text-center text-gray-600">
                {{ $t('There are no events on that day, future or past.') }}
              </p>
            </div>
          </div>

          <!-- no day selected: blank state -->
          <div
            v-else
            class="flex items-center rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-slate-900 sm:p-0">
            <div>
              <img src="/img/calendar_day_blank.svg" :alt="$t('Groups')" class="mx-auto mt-4 h-36 w-36" />
              <p class="px-5 pb-5 pt-2 text-center">{{ $t('Click on a day to see the details') }}</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<style lang="scss" scoped>
.special-grid {
  grid-template-columns: 1fr 250px;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}
</style>
