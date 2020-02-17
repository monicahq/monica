<style scoped>
.avatar-padding {
  padding-top: 19%
}
</style>

<template>
  <span>
    <img v-if="check"
         v-tooltip.bottom="contact.complete_name"
         :class="['br4 h3 w3 dib tc', imgclass]"
         :alt="contact.initials"
         :src="avatar_url"
         @error="check=false"
    />
    <span v-else
          v-tooltip.bottom="contact.complete_name"
          :class="['br4 h3 w3 dib tc', 'white f3 avatar-padding', imgclass]"
          :style="'background-color: '+ default_avatar_color"
    >
      {{ contact.initials }}
    </span>
  </span>
</template>

<script>
export default {
  props: {
    contact: {
      type: Object,
      default: null,
    },
    imgclass: {
      type: String,
      default: '',
    },
  },
  data () {
    return {
      check: true,
    };
  },
  computed: {
    avatar_url() {
      if (_.isObject(this.contact.information)) {
        return this.contact.information.avatar.url;
      }
      return this.contact.avatar_url;
    },
    default_avatar_color() {
      if (_.isObject(this.contact.information)) {
        return this.contact.information.avatar.default_avatar_color;
      }
      return this.contact.default_avatar_color;
    }
  }
};
</script>
