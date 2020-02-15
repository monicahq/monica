<style lang="scss" scoped>
.contacts {
  li:last-child {
    border-bottom: 0;
  }
}
</style>

<template>
  <div>
    <!-- list of tags displayed in the contact list, on the right -->
    <ul class="ma0 list pl0">
      <li class="mb2">{{ $t('people.people_list_browse_by_tags') }}</li>
      <li class="mb2" v-for="tag in shortList" :key="tag.id">
        <inertia-link :href="tag.url">{{ tag.name }}</inertia-link>
        <span class="fr">{{ tag.count }}</span>
      </li>
      <li v-if="!showFullList"><a href="#" @click.prevent="showFullList = true">{{ $t('people.people_list_tags_complete_list', { count: longList.length }) }}</a></li>
    </ul>
    <ul class="ma0 list pl0" v-if="showFullList">
      <li class="mb2" v-for="tag in longList" :key="tag.id">
        <inertia-link :href="tag.url">{{ tag.name }}</inertia-link>
        <span class="fr">{{ tag.count }}</span>
      </li>
    </ul>
  </div>
</template>

<script>

export default {
  props: {
    tags: {
      type: Array,
      default: null,
    },
  },

  data() {
    return {
      shortList: [],
      longList: [],
      showFullList: false,
    };
  },

  created: function() {
    this.shortList = this.tags.splice(0, 5);
    this.longList = this.tags.splice(0, this.tags.length - 5);
  },
};
</script>
