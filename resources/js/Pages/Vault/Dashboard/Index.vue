<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import LastUpdated from '@/Pages/Vault/Dashboard/Partials/LastUpdated.vue';
import UpcomingReminders from '@/Pages/Vault/Dashboard/Partials/UpcomingReminders.vue';
import Favorites from '@/Pages/Vault/Dashboard/Partials/Favorites.vue';
import DueTasks from '@/Pages/Vault/Dashboard/Partials/DueTasks.vue';
import LifeMetrics from '@/Pages/Vault/Dashboard/Partials/LifeMetrics.vue';
import MoodTrackingEvents from '@/Pages/Vault/Dashboard/Partials/MoodTrackingEvents.vue';
import Feed from '@/Shared/Modules/Feed.vue';
import LifeEvent from '@/Shared/Modules/LifeEvent.vue';
import { SquareActivity, Flame, ChartSpline } from 'lucide-vue-next';

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
              <div class="mb-8 inline-flex rounded-md shadow-xs">
                <!-- Activity in the vault -->
                <button
                  @click="changeTab('activity')"
                  type="button"
                  :class="
                    currentTab === 'activity'
                      ? 'bg-gray-100 text-blue-700 dark:bg-gray-900 dark:text-blue-300'
                      : 'bg-white text-gray-900 dark:bg-gray-700 dark:text-white'
                  "
                  class="inline-flex cursor-pointer items-center rounded-s-lg border-y border-s border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-blue-300 dark:focus:ring-blue-500">
                  <SquareActivity class="me-2 h-4 w-4" />
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
                  class="inline-flex cursor-pointer items-center border-y border-e border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-blue-300 dark:focus:ring-blue-500">
                  <Flame class="me-2 h-4 w-4" />
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
                  class="inline-flex cursor-pointer items-center rounded-e-lg border-y border-e border-gray-200 px-4 py-2 text-sm font-medium hover:bg-gray-100 hover:text-blue-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-blue-300 dark:focus:ring-blue-500">
                  <ChartSpline class="me-2 h-4 w-4" />
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
