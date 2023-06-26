<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Layout from '@/Shared/Layout.vue';
import LastUpdated from '@/Pages/Vault/Dashboard/Partials/LastUpdated.vue';
import UpcomingReminders from '@/Pages/Vault/Dashboard/Partials/UpcomingReminders.vue';
import Favorites from '@/Pages/Vault/Dashboard/Partials/Favorites.vue';
import DueTasks from '@/Pages/Vault/Dashboard/Partials/DueTasks.vue';
import LifeMetrics from '@/Pages/Vault/Dashboard/Partials/LifeMetrics.vue';
import MoodTrackingEvents from '@/Pages/Vault/Dashboard/Partials/MoodTrackingEvents.vue';
import Feed from '@/Shared/Modules/Feed.vue';
import LifeEvent from '@/Shared/Modules/LifeEvent.vue';

const props = defineProps({
  layoutData: Object,
  data: Object,
  lastUpdatedContacts: Object,
  upcomingReminders: Object,
  favorites: Object,
  url: Array,
  dueTasks: Object,
  moodTrackingEvents: Object,
  lifeEvents: Object,
  lifeMetrics: Object,
  defaultTab: String,
});

const currentTab = ref(props.defaultTab);

const form = useForm({
  default_activity_tab: null,
});

const changeTab = (tab) => {
  currentTab.value = tab;
  form.default_activity_tab = tab;

  axios.put(props.url.default_tab, form);
};
</script>

<template>
  <Layout title="Dashboard" :inside-vault="true" :layout-data="layoutData">
    <main class="relative sm:mt-24">
      <div class="max-w-8xl mx-auto py-2 sm:px-6 sm:py-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-0">
            <!-- favorites -->
            <Favorites v-if="favorites.length > 0" :data="favorites" />

            <!-- last updated contacts -->
            <LastUpdated :data="lastUpdatedContacts" />
          </div>

          <!-- middle -->
          <div class="p-3 sm:p-0">
            <!-- tabs -->
            <div class="flex justify-center">
              <div class="mb-8 inline-flex rounded-md shadow-sm">
                <!-- Activity in the vault -->
                <button
                  @click="changeTab('activity')"
                  type="button"
                  :class="
                    currentTab === 'activity'
                      ? 'bg-gray-100 text-blue-700 dark:bg-gray-900 dark:text-blue-300'
                      : 'bg-white text-gray-900 dark:bg-gray-700 dark:text-white'
                  "
                  class="inline-flex items-center rounded-s-lg border-y border-s border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 hover:dark:bg-gray-600 hover:dark:text-blue-300 dark:focus:ring-blue-500">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="me-2 h-4 w-4 fill-current">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" />
                  </svg>
                  {{ $t('Activity in this vault') }}
                </button>

                <!-- Your life events -->
                <button
                  @click="changeTab('life_events')"
                  type="button"
                  :class="
                    currentTab === 'life_events'
                      ? 'bg-gray-100 text-blue-700 dark:bg-gray-900 dark:text-blue-300'
                      : 'bg-white text-gray-900 dark:bg-gray-700 dark:text-white'
                  "
                  class="inline-flex items-center border-y border-e border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 hover:dark:bg-gray-600 hover:dark:text-blue-300 dark:focus:ring-blue-500">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="me-2 h-4 w-4">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                  </svg>
                  {{ $t('Your life events') }}
                </button>

                <!-- Your life metrics -->
                <button
                  @click="changeTab('life_metrics')"
                  type="button"
                  :class="
                    currentTab === 'life_metrics'
                      ? 'bg-gray-100 text-blue-700 dark:bg-gray-900 dark:text-blue-300'
                      : 'bg-white text-gray-900 dark:bg-gray-700 dark:text-white'
                  "
                  class="inline-flex items-center rounded-e-lg border-y border-e border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 hover:dark:bg-gray-600 hover:dark:text-blue-300 dark:focus:ring-blue-500">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="me-2 h-4 w-4">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                  </svg>
                  {{ $t('Life metrics') }}
                </button>
              </div>
            </div>

            <!-- feed tab -->
            <Feed v-if="currentTab === 'activity'" :url="url.feed" :contact-view-mode="false" />

            <!-- life events -->
            <LifeEvent v-else-if="currentTab === 'life_events'" :data="lifeEvents" :layout-data="layoutData" />

            <!-- life metrics tab -->
            <LifeMetrics v-else-if="currentTab === 'life_metrics'" :data="lifeMetrics" />
          </div>

          <!-- right -->
          <div class="p-3 sm:p-0">
            <!-- mood tracking -->
            <MoodTrackingEvents :data="moodTrackingEvents" />

            <!-- upcoming reminders -->
            <UpcomingReminders :data="upcomingReminders" />

            <!-- tasks -->
            <DueTasks :data="dueTasks" />
          </div>
        </div>
      </div>
    </main>
  </Layout>
</template>

<style lang="scss" scoped>
.grid {
  grid-template-columns: 200px 1fr 400px;
}

@media (max-width: 480px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
