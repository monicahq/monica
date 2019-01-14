<style scoped>
</style>

<template>
  <div>
    <template v-if="clickable == true">
      <a :href="'/people/' + contact.id">
        <img v-if="contact.avatar_url" v-tooltip.bottom="contact.complete_name" :src="contact.avatar_url" class="br4 h3 w3 dib tc" />
        <div v-else v-tooltip.bottom="contact.complete_name" :style="{ 'background-color': contact.default_avatar_color }" class="br4 h3 w3 dib pt3 white tc f4">
          {{ contact.initials }}
        </div>
      </a>
    </template>
    <template v-else>
      <img v-if="contact.avatar_url" v-tooltip.bottom="contact.complete_name" :src="contact.avatar_url" class="br4 h3 w3 dib tc" />
      <div v-else v-tooltip.bottom="contact.complete_name" :style="{ 'background-color': contact.default_avatar_color }" class="br4 h3 w3 dib pt3 white tc f4">
        {{ contact.initials }}
      </div>
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
