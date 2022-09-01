<template>
  <div class="mb-4">
    <div class="ml-4 border-l border-gray-200">
      <div v-for="feedItem in data.items" :key="feedItem.id" class="mb-8">
        <!-- action & user -->
        <div class="mb-3 flex">
          <div class="icon-avatar relative w-6">
            <avatar
              :data="feedItem.author.avatar"
              :classes="'rounded-full border-gray-200 dark:border-gray-800 border relative h-5 w-5'" />
          </div>

          <div>
            <p class="mr-2 inline text-gray-400">
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
            <p class="mr-2 inline">•</p>
            <p class="inline text-sm text-gray-400">
              {{ feedItem.created_at }}
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
            v-if="feedItem.action === 'contact_information_updated'"
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

          <label-assigned
            v-if="feedItem.action === 'label_assigned'"
            :data="feedItem.data"
            :contact-view-mode="contactViewMode" />

          <label-assigned
            v-if="feedItem.action === 'label_removed'"
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
    <div v-if="data.items.length == 0">
      <p class="p-5 text-center">There is no activity yet.</p>
    </div>
  </div>
</template>

<script>
import Avatar from '@/Shared/Avatar.vue';
import GenericAction from '@/Shared/Modules/FeedItems/GenericAction.vue';
import LabelAssigned from '@/Shared/Modules/FeedItems/LabelAssigned.vue';

export default {
  components: {
    Avatar,
    GenericAction,
    LabelAssigned,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
    contactViewMode: {
      type: Boolean,
      default: true,
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
