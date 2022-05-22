<style lang="scss" scoped>
.icon-avatar {
  top: -1px;
  left: -12px;
}
</style>

<template>
  <div class="mb-4">
    <div class="ml-4 border-l border-gray-200">
      <div v-for="feedItem in data.items" :key="feedItem.id" class="mb-8">
        <!-- action & user -->
        <div class="mb-3 flex">
          <div class="relative w-6">
            <div v-html="feedItem.author.avatar" class="icon-avatar relative h-6 w-6"></div>
          </div>

          <div>
            <p class="mr-2 inline text-gray-400">
              <span class="font-medium text-gray-800">{{ feedItem.author.name }}</span> {{ feedItem.sentence }}
            </p>
            <p class="mr-2 inline">•</p>
            <p class="inline text-sm text-gray-400">{{ feedItem.created_at }}</p>
          </div>
        </div>

        <!-- details -->
        <div v-if="feedItem.description" class="ml-6">
          <div class="rounded-lg border border-gray-300 px-3 py-2">
            <span class="text-sm">{{ feedItem.description }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- blank state -->
    <div v-if="data.items.length == 0">
      <p class="p-5 text-center">This person doesn't have any activity yet.</p>
    </div>
  </div>
</template>

<script>
import Note from '@/Pages/Vault/Dashboard/Partials/Feed/Note';
import FeedItem from '@/Pages/Vault/Dashboard/Partials/Feed/FeedItem';

export default {
  components: {
    Note,
    FeedItem,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },
};
</script>
