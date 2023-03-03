<script setup>
import Layout from '@/Shared/Layout.vue';
import LastUpdated from '@/Pages/Vault/Dashboard/Partials/LastUpdated.vue';
import UpcomingReminders from '@/Pages/Vault/Dashboard/Partials/UpcomingReminders.vue';
import Favorites from '@/Pages/Vault/Dashboard/Partials/Favorites.vue';
import DueTasks from '@/Pages/Vault/Dashboard/Partials/DueTasks.vue';
import MoodTrackingEvents from '@/Pages/Vault/Dashboard/Partials/MoodTrackingEvents.vue';
import Feed from '@/Shared/Modules/Feed.vue';
import LifeEvent from '@/Shared/Modules/LifeEvent.vue';
import { onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/inertia-vue3';

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
  activityTabShown: String,
});

const defaultTab = ref('activity');

const form = useForm({
  show_activity_tab_on_dashboard: null,
});

onMounted(() => {
  if (props.activityTabShown) {
    defaultTab.value = 'activity';
  } else {
    defaultTab.value = 'life_events';
  }
});

const changeTab = (tab) => {
  defaultTab.value = tab;

  if (defaultTab.value === 'activity') {
    form.show_activity_tab_on_dashboard = 1;
  } else {
    form.show_activity_tab_on_dashboard = 0;
  }

  axios.put(props.url.default_tab, form);
};
</script>

<template>
  <layout title="Dashboard" :inside-vault="true" :layout-data="layoutData">
    <main class="relative sm:mt-24">
      <div class="max-w-8xl mx-auto py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-0">
            <!-- favorites -->
            <favorites v-if="favorites.length > 0" :data="favorites" />

            <!-- last updated contacts -->
            <last-updated :data="lastUpdatedContacts" />
          </div>

          <!-- middle -->
          <div class="p-3 sm:p-0">
            <!-- tabs -->
            <div class="flex justify-center">
              <div class="mb-8 inline-flex rounded-md shadow-sm">
                <button
                  @click="changeTab('activity')"
                  type="button"
                  :class="{ 'bg-gray-100 text-blue-700 dark:bg-gray-400 dark:font-bold': defaultTab === 'activity' }"
                  class="inline-flex items-center rounded-l-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-blue-500">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="mr-2 h-4 w-4 fill-current">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" />
                  </svg>

                  Activity in this vault
                </button>

                <button
                  @click="changeTab('life_events')"
                  type="button"
                  :class="{ 'bg-gray-100 text-blue-700 dark:bg-gray-400 dark:font-bold': defaultTab === 'life_events' }"
                  class="inline-flex items-center rounded-r-md border-t border-b border-r border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-blue-500">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="mr-2 h-4 w-4">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                  </svg>
                  Your life events
                </button>
              </div>
            </div>

            <life-event v-if="defaultTab == 'life_events'" :data="lifeEvents" :layout-data="layoutData" />

            <!-- feed tab -->
            <div v-if="defaultTab == 'activity'">
              <feed :url="url.feed" :contact-view-mode="false" />
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:p-0">
            <!-- mood tracking -->
            <mood-tracking-events :data="moodTrackingEvents" />

            <!-- upcoming reminders -->
            <upcoming-reminders :data="upcomingReminders" />

            <!-- tasks -->
            <due-tasks :data="dueTasks" />
          </div>
        </div>
      </div>
    </main>
  </layout>
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
