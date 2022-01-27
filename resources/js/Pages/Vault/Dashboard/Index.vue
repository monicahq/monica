<style lang="scss" scoped>
.grid {
  grid-template-columns: 200px 1fr 200px;
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

input[type=checkbox] {
  top: 3px;
  width: 12px;
}
</style>

<template>
  <layout title="Dashboard" :inside-vault="true" :layout-data="layoutData">
    <main class="sm:mt-24 relative">
      <div class="max-w-8xl mx-auto py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <!-- left -->
          <div class="p-3 sm:p-0">
            <!-- favorites -->
            <h3 class="font-medium border-b border-gray-200 mb-3">
              <span class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon-sidebar h-4 w-4 inline relative text-gray-300 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
              </span>
              Favorites
            </h3>
            <div class="flex text-sm items-center mb-2">
              <small-contact />
            </div>
            <div class="flex text-sm items-center mb-2">
              <small-contact />
            </div>
          </div>

          <!-- middle -->
          <div class="p-3 sm:p-0">
            <!-- actions -->
            <div style="background-color: #fcfeff;" class="bg-white border border-gray-200 rounded-lg mb-6">
              <div v-if="!addMode" class="p-3">
                <p class="text-center mb-5"><span class="mr-2">👋</span> Good evening, Regis.</p>

                <div class="sm:flex justify-center mb-2">
                  <pretty-button :text="'life event'" :icon="'plus'" :classes="'mr-3'" @click="showAddModal('lifeEvent')" />
                  <pretty-button :text="'activity'" :icon="'plus'" :classes="'mr-3'" />
                  <pretty-button :text="'entry'" :icon="'plus'" :classes="'mr-3'" />
                  <pretty-button :text="'mood'" :icon="'plus'" :classes="'mr-3'" />
                  <pretty-button :text="'communication'" :icon="'plus'" :classes="'mr-3'" />
                  <pretty-button :text="'goal'" :icon="'plus'" />
                </div>
              </div>

              <div v-if="addMode" class="p-5">
                <create-life-event @cancelled="addMode = false" />
              </div>
            </div>

            <!-- filters -->
            <div class="mb-3 text-right">
              <button
                class="inline-flex items-center px-2 py-1 border border-gray-300 rounded-md shadow-sm text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
              </button>
            </div>

            <!-- feed -->
            <div class="mb-10 overflow-auto">
              <h3 class="mb-5 font-bold">
                Janvier 2010
              </h3>

              <!-- journal entry -->
              <entry />

              <feed-item />

              <feed-item />

              <!-- activity -->
              <activity />

              <!-- goal -->
              <goal />
            </div>

            <!-- archives -->
            <div>
              <p class="text-xs">Browse past entries</p>
              <ul>
                <li class="inline mr-2 text-sm"><a class="underline" href="">2021</a> <span class="text-gray-500 text-xs">(3)</span></li>
                <li class="inline mr-2 text-sm"><a class="underline" href="">2020</a> <span class="text-gray-500 text-xs">(139)</span></li>
                <li class="inline mr-2 text-sm"><a class="underline" href="">2019</a> <span class="text-gray-500 text-xs">(23)</span></li>
              </ul>
            </div>
          </div>

          <!-- right -->
          <div class="p-3 sm:p-0">
            <div class="mb-10">
              <h3 class="font-medium mb-3 border-b border-gray-200">
                <span class="relative">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon-sidebar h-4 w-4 inline relative text-gray-300 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </span>
                Reminders
              </h3>
              <p class="text-xs text-gray-400">Nov 09</p>
              <p class="mb-3 text-sm">Birthday John</p>
              <p class="text-xs text-gray-400">Nov 13</p>
              <p class="mb-3 text-sm">Wedding Henri</p>
            </div>
            <h3 class="font-medium mb-3 border-b border-gray-200">
              <span class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon-sidebar h-4 w-4 inline relative text-gray-300 hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
              </span>
              Tasks
            </h3>
            <div class="flex items-start mb-3 relative">
              <input id="remember-me" name="remember-me" type="checkbox" class="relative h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
              <label for="remember-me" class="cursor-pointer ml-2 block text-sm text-gray-900">
                Remember mea sdfasdf asdf asdf asdf sdf
              </label>
            </div>
            <div class="flex items-start">
              <input id="remember-me" name="remember-me" type="checkbox" class="relative h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
              <label for="remember-me" class="cursor-pointer ml-2 block text-sm text-gray-900">
                Remember me
              </label>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import SmallContact from '@/Shared/SmallContact';
import Activity from '@/Pages/Vault/Dashboard/Partials/Feed/Activity';
import Entry from '@/Pages/Vault/Dashboard/Partials/Feed/Entry';
import Goal from '@/Pages/Vault/Dashboard/Partials/Feed/Goal';
import FeedItem from '@/Pages/Vault/Dashboard/Partials/Feed/FeedItem';
import CreateLifeEvent from '@/Pages/Vault/Dashboard/Partials/Feed/CreateLifeEvent';
import PrettyButton from '@/Shared/Form/PrettyButton';

export default {
  components: {
    Layout,
    PrettyButton,
    SmallContact,
    Entry,
    Activity,
    Goal,
    FeedItem,
    CreateLifeEvent,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Array,
      default: () => [],
    },
  },

  data() {
    return {
      addMode: false,
    };
  },

  methods: {
    showAddModal(type) {
      if (type == 'lifeEvent') {
        this.addMode = true;
      }
    },
  },
};
</script>
