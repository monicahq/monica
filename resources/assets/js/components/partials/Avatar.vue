<style scoped>
</style>

<template>
  <div>
    <template v-if="clickable == true">
      <a :href="'/people/' + contact.id">
        <img :src="contact.avatar_url" class="br4 h3 w3 dib tc" v-tooltip.bottom="contact.complete_name">
      </a>
    </template>
    <template v-else>
      <img :src="contact.avatar_url" class="br4 h3 w3 dib tc" v-tooltip.bottom="contact.complete_name">
    </template>
  </div>
</template>

<script>
export default {
  props: {
    contact: {
      type: Object,
      default: null,
    },
    clickable: {
      type: Boolean,
      default: true,
    },
  },
};
</script>
