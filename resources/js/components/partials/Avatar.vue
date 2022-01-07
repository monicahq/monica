<template>
  <div class="avatars">
    <a v-if="clickable === true" :href="'people/' + id">
      <avatarimg :contact="contact" :imgclass="imgclass" />
    </a>
    <avatarimg v-else :contact="contact" :imgclass="imgclass" />
  </div>
</template>

<script>
import Avatarimg from './Avatar.img.vue';

export default {
  components: {
    Avatarimg
  },

  props: {
    contact: {
      type: Object,
      default: null,
    },
    clickable: {
      type: Boolean,
      default: true,
    },
    imgclass: {
      type: String,
      default: '',
    },
  },
  computed: {
    id() {
      return this.contact.hash_id ? this.contact.hash_id : this.contact.id;
    },
  }
};
</script>
