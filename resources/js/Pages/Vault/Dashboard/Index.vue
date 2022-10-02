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

.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

input[type='checkbox'] {
  top: 3px;
  width: 12px;
}
</style>
