<style scoped>
</style>

<template>
  <div>
    <template v-if="clickable == true">
      <a :href="'people/' + contact.id">
        <img v-if="check"
             v-tooltip.bottom="contact.complete_name"
             class="br4 h3 w3 dib tc"
             :alt="contact.initials"
             :src="contact.avatar_url"
             @error="check=false"
        />
        <div v-else
             v-tooltip.bottom="contact.complete_name"
             class="br4 h3 w3 dib tc white f3"
             :style="'padding-top: 12px; background-color: '+ contact.default_avatar_color"
        >
          {{ contact.initials }}
        </div>
      </a>
    </template>
    <template v-else>
      <img v-if="check"
           v-tooltip.bottom="contact.complete_name"
           class="br4 h3 w3 dib tc"
           :alt="contact.initials"
           :src="contact.avatar_url"
           @error="check=false"
      />
      <div v-else
           v-tooltip.bottom="contact.complete_name"
           class="br4 h3 w3 dib tc white f3"
           :style="'padding-top: 12px; background-color: '+ contact.default_avatar_color"
      >
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
  data () {
    return {
      check: true,
    };
  }
};
</script>
