<template>
  <div>
    <avatar :data="data.avatar" :class="'mx-auto mb-6 w-32 sm:w-1/2'" :img-classes="'rounded-xs sm:w-72'" />
  </div>
</template>

<script>
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
    Avatar,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },
};
</script>
