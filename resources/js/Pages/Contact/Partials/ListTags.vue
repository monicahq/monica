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
      <li class="mb2">Browse by tags</li>
      <li class="mb2" v-for="tag in tags" :key="tag.id">
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
};
</script>
