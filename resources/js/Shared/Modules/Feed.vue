<template>
  <div class="mb-4">
    <div class="ml-4 border-l border-gray-200 dark:border-gray-700">
      <div v-for="feedItem in feed" :key="feedItem.id" class="mb-8">
        <!-- action & user -->
        <div class="mb-2 flex">
          <div class="icon-avatar relative w-6">
            <avatar
              :data="feedItem.author.avatar"
              :classes="'rounded-full border-gray-200 dark:border-gray-800 border relative h-5 w-5'" />
          </div>

          <div class="flex w-full items-center justify-between">
            <p class="mr-2 text-gray-400">
              <!-- author name + link to profile -->
              <inertia-link
                v-if="feedItem.author.url"
                :href="feedItem.author.url"
                class="font-medium text-gray-800 hover:underline dark:text-gray-200"
                >{{ feedItem.author.name }}</inertia-link
              >
              <span v-else class="font-medium text-gray-800 dark:text-gray-200">{{ feedItem.author.name }}</span>

              <!-- action done -->
              <span class="ml-2">{{ feedItem.sentence }}</span>
            </p>

            <p class="flex items-center text-sm text-gray-400">
              <span>{{ feedItem.created_at }}</span>

              <svg
                class="ml-2 h-4 w-4"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
              </svg>
            </p>
          </div>
        </div>

        <!-- feed object -->
        <div v-if="feedItem.data" class="ml-6">
          <generic-action
            v-if="feedItem.action === 'contact_created'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <generic-action
            v-if="feedItem.action === 'information_updated'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <generic-action
            v-if="feedItem.action === 'job_information_updated'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <generic-action
            v-if="feedItem.action === 'archived'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <generic-action
            v-if="feedItem.action === 'unarchived'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <generic-action
            v-if="feedItem.action === 'changed_avatar'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <generic-action
            v-if="feedItem.action === 'favorited'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <generic-action
            v-if="feedItem.action === 'religion_updated'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <label-assigned
            v-if="feedItem.action === 'label_assigned'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <label-assigned
            v-if="feedItem.action === 'label_removed'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <addresses
            v-if="
              feedItem.action === 'address_created' ||
              feedItem.action === 'address_updated' ||
              feedItem.action === 'address_destroyed'
            "
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <contact-information
            v-if="
              feedItem.action === 'contact_information_created' ||
              feedItem.action === 'contact_information_updated' ||
              feedItem.action === 'contact_information_destroyed'
            "
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <pet
            v-if="
              feedItem.action === 'pet_created' ||
              feedItem.action === 'pet_updated' ||
              feedItem.action === 'pet_destroyed'
            "
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <goal
            v-if="
              feedItem.action === 'goal_created' ||
              feedItem.action === 'goal_updated' ||
              feedItem.action === 'goal_destroyed'
            "
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <mood-tracking-event
            v-if="
              feedItem.action === 'mood_tracking_event_added' ||
              feedItem.action === 'mood_tracking_event_updated' ||
              feedItem.action === 'mood_tracking_event_deleted'
            "
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />
        </div>

        <!-- details -->
        <div v-if="feedItem.description" class="ml-6">
          <div class="rounded-lg border border-gray-300 px-3 py-2">
            <span class="text-sm">
              {{ feedItem.description }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- blank state -->
    <div v-if="feed.length == 0 && !loading">
      <p class="p-5 text-center">There is no activity yet.</p>
      <img src="/img/dashboard_blank_activity_feed.svg" :alt="$t('Activity feed')" class="mx-auto h-96 w-96" />
    </div>

    <!-- loading mode -->
    <div v-if="loading" class="mb-5 rounded-lg border border-gray-200 p-20 text-center">
      <loading />
    </div>

    <!-- load more -->
    <div class="text-center" v-if="paginator.hasMorePages">
      <span
        @click="load()"
        class="cursor-pointer rounded border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
        {{ $t('app.view_older') }}
      </span>
    </div>
  </div>
</template>

<script>
import Loading from '@/Shared/Loading.vue';
import Avatar from '@/Shared/Avatar.vue';
import GenericAction from '@/Shared/Modules/FeedItems/GenericAction.vue';
import LabelAssigned from '@/Shared/Modules/FeedItems/LabelAssigned.vue';
import Addresses from '@/Shared/Modules/FeedItems/Address.vue';
import ContactInformation from '@/Shared/Modules/FeedItems/ContactInformation.vue';
import Pet from '@/Shared/Modules/FeedItems/Pet.vue';
import Goal from '@/Shared/Modules/FeedItems/Goal.vue';
import MoodTrackingEvent from '@/Shared/Modules/FeedItems/MoodTrackingEvent.vue';

export default {
  components: {
    Loading,
    Avatar,
    GenericAction,
    LabelAssigned,
    Addresses,
    ContactInformation,
    Pet,
    Goal,
    MoodTrackingEvent,
  },

  props: {
    contactViewMode: {
      type: Boolean,
      default: true,
    },
    url: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      feed: [],
      loading: false,
      paginator: [],
    };
  },

  mounted() {
    this.initialLoad();
  },

  methods: {
    initialLoad() {
      this.loading = true;
      axios
        .get(this.url)
        .then((response) => {
          this.loading = false;
          response.data.data.items.forEach((entry) => {
            this.feed.push(entry);
          });
          this.paginator = response.data.paginator;
        })
        .catch(() => {});
    },

    load() {
      axios
        .get(this.paginator.nextPageUrl)
        .then((response) => {
          response.data.data.items.forEach((entry) => {
            this.feed.push(entry);
          });
          this.paginator = response.data.paginator;
        })
        .catch(() => {});
    },
  },
};
</script>

<style lang="scss" scoped>
.icon-avatar {
  top: 3px;
  left: -11px;
}
</style>
