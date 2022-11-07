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
            <h3 class="mb-3 flex items-center border-b border-gray-200 pb-1 font-medium dark:border-gray-700">
              <span class="relative mr-2">
                <svg
                  class="icon-sidebar relative inline h-4 w-4 text-gray-500 dark:text-gray-300 hover:dark:text-gray-400"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
              </span>

              <span class="mr-2 inline">
                {{ $t('vault.dashboard_feed_title') }}
              </span>

              <help :url="$page.props.help_links.last_updated_contacts" :top="'4px'" />
            </h3>
            <feed :url="loadFeedUrl" :contact-view-mode="false" />
          </div>

          <!-- right -->
          <div class="p-3 sm:p-0">
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

<script>
import Layout from '@/Shared/Layout.vue';
import LastUpdated from '@/Pages/Vault/Dashboard/Partials/LastUpdated.vue';
import UpcomingReminders from '@/Pages/Vault/Dashboard/Partials/UpcomingReminders.vue';
import Favorites from '@/Pages/Vault/Dashboard/Partials/Favorites.vue';
import DueTasks from '@/Pages/Vault/Dashboard/Partials/DueTasks.vue';
import Feed from '@/Shared/Modules/Feed.vue';

export default {
  components: {
    Layout,
    LastUpdated,
    UpcomingReminders,
    Favorites,
    DueTasks,
    Feed,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    lastUpdatedContacts: {
      type: Object,
      default: null,
    },
    upcomingReminders: {
      type: Object,
      default: null,
    },
    favorites: {
      type: Object,
      default: null,
    },
    loadFeedUrl: {
      type: String,
      default: null,
    },
    dueTasks: {
      type: Object,
      default: null,
    },
  },
};
</script>

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
