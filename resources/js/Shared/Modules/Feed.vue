<template>
  <div class="mb-4">
    <div class="ms-4 border-s border-gray-200 dark:border-gray-700">
      <div v-for="feedItem in feed" :key="feedItem.id" class="mb-8">
        <!-- Ensure type exists before rendering -->
        <div v-if="feedItem && feedItem.type === 'feed'">
          <!-- action & user -->
          <div class="mb-2 flex">
            <div class="relative -start-[11px] top-[3px] w-6">
              <avatar
                :data="feedItem.author.avatar"
                :class="'relative h-5 w-5 rounded-full border border-gray-200 dark:border-gray-800'" />
            </div>

            <div class="flex flex-col md:flex-row w-full md:items-center justify-between">
              <p class="me-2 text-gray-400">
                <!-- author name + link to profile -->
                <InertiaLink
                  v-if="feedItem.author.url"
                  :href="feedItem.author.url"
                  class="font-medium text-gray-800 hover:underline dark:text-gray-200"
                  >{{ feedItem.author.name }}</InertiaLink
                >
                <span v-else class="font-medium text-gray-800 dark:text-gray-200">{{ feedItem.author.name }}</span>
                <span class="ms-2">{{ feedItem.sentence }}</span>
              </p>

              <!-- date of the action -->
              <p class="mb-1 md:mb-0 flex items-center text-sm text-gray-400">
                <span>{{ feedItem.created_at }}</span>

                <CalendarIcon :type="'empty'" />
              </p>
            </div>
          </div>
          <!-- feed object -->
          <div v-if="feedItem.data" class="ms-6">
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

            <note
              v-if="
                feedItem.action === 'note_created' ||
                feedItem.action === 'note_updated' ||
                feedItem.action === 'note_deleted'
              "
              :data="feedItem.data"
              :contact-view-mode="contactViewMode" />
          </div>
          <!-- details -->
          <div v-if="feedItem.description" class="ms-6">
            <div class="rounded-lg border border-gray-300 px-3 py-2">
              <span class="text-sm">
                {{ feedItem.description }}
              </span>
            </div>
          </div>
        </div>

        <!-- Ensure the feedItem exists and is a post -->
        <div v-if="feedItem && feedItem.type === 'post'">
          <!-- Post header: display title and creation date -->
          <div class="mb-2 flex">
            <div class="flex flex-col md:flex-row w-full md:items-center justify-between">
              <p class="me-2 text-gray-400">
                <span class="ms-2">A post involving this contact was created: </span>
                <span class="font-bold text-gray-800 dark:text-gray-200">{{ feedItem.title }}</span>
              </p>

              <p class="mb-1 md:mb-0 flex items-center text-sm text-gray-400">
                <span>{{ feedItem.created_at }}</span>
                <CalendarIcon :type="'empty'" />
              </p>
            </div>
          </div>

          <!-- Post content -->
          <div class="ms-6">
            <div class="rounded-lg border border-gray-300 px-3 py-2 bg-gray-50 dark:bg-gray-800">
              <p
                class="text-sm text-gray-700 dark:text-gray-300"
                v-html="convertMentions(feedItem.content, feedItem.contacts, contactId)"></p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- blank state -->
    <div v-if="feed.length === 0 && !loading">
      <p class="p-5 text-center">{{ $t('There is no activity yet.') }}</p>
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
        class="cursor-pointer rounded-sm border border-gray-200 px-3 py-1 text-sm text-blue-500 hover:border-gray-500 dark:border-gray-700">
        {{ $t('Load previous entries') }}
      </span>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import Loading from '@/Shared/Loading.vue';
import Avatar from '@/Shared/Avatar.vue';
import GenericAction from '@/Shared/Modules/FeedItems/GenericAction.vue';
import LabelAssigned from '@/Shared/Modules/FeedItems/LabelAssigned.vue';
import Addresses from '@/Shared/Modules/FeedItems/Address.vue';
import ContactInformation from '@/Shared/Modules/FeedItems/ContactInformation.vue';
import Pet from '@/Shared/Modules/FeedItems/Pet.vue';
import Goal from '@/Shared/Modules/FeedItems/Goal.vue';
import MoodTrackingEvent from '@/Shared/Modules/FeedItems/MoodTrackingEvent.vue';
import Note from '@/Shared/Modules/FeedItems/Note.vue';
import CalendarIcon from '@/Shared/Icons/CalendarIcon.vue';
import { convertMentions } from '@/utils/mentionUtils.js';

export default {
  components: {
    InertiaLink: Link,
    Loading,
    Avatar,
    GenericAction,
    LabelAssigned,
    Addresses,
    ContactInformation,
    Pet,
    Goal,
    MoodTrackingEvent,
    Note,
    CalendarIcon,
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
    contactId: {
      type: String,
    },
  },

  data() {
    return {
      feed: [], // Ensure it's an empty array by default
      loading: false,
      paginator: {},
      authors: {}, // Placeholder for author names
    };
  },

  mounted() {
    this.initialLoad();
  },

  methods: {
    convertMentions,
    initialLoad() {
      this.loading = true;
      axios
        .get(this.url)
        .then((response) => {
          this.loading = false;
          // Access the "items" key inside data
          if (Array.isArray(response.data.data.items)) {
            this.feed = response.data.data.items;
          } else {
            console.error('Invalid API response: `data.items` is not an array', response.data);
            this.feed = [];
          }
          this.paginator = response.data.paginator || {};
        })
        .catch((error) => {
          console.error('API Error:', error);
          this.loading = false;
          this.feed = [];
        });
    },

    load() {
      axios
        .get(this.paginator.nextPageUrl)
        .then((response) => {
          if (Array.isArray(response.data.data.items)) {
            this.feed = [...this.feed, ...response.data.data.items];
          }
          this.paginator = response.data.paginator || {};
        })
        .catch(() => {});
    },

    getAuthorName(feedItem) {
      if (feedItem.author && feedItem.author.name) {
        return `${feedItem.author.name}`;
      }
      return 'Unknown User';
    },

    getActionDescription(action) {
      const actionMap = {
        contact_created: 'created a contact',
        information_updated: 'updated contact information',
        job_information_updated: 'updated job details',
        archived: 'archived this contact',
        unarchived: 'unarchived this contact',
        changed_avatar: 'changed avatar',
        favorited: 'favorited this contact',
        religion_updated: 'updated religion details',
      };
      return actionMap[action] || 'performed an action';
    },
  },
};
</script>
