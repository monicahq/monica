<style lang="scss" scoped>
.icon-sidebar {
  color: #737e8d;
  top: -2px;
}

.icon-note {
  top: -1px;
}
</style>

<template>
  <div class="mb-4">
    <div v-for="item in data.items" :key="item.id">
      <note v-if="item.action == 'note_created'" :note="item.object" />

      <feed-item v-if="item.action == 'important_date_created'" :message="item.object" />
      <feed-item v-if="item.action == 'important_date_updated'" :message="item.object" />
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
