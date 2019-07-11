<style scoped>
</style>

<template>
  <div>
    <template v-if="clickable == true">
      <a :href="'people/' + contact.id">
        <img v-tooltip.bottom="contact.complete_name" :src="contact.avatar_url" class="br4 h3 w3 dib tc" />
      </a>
    </template>
    <template v-else>
      <img v-tooltip.bottom="contact.complete_name" :src="contact.avatar_url" class="br4 h3 w3 dib tc" />
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
