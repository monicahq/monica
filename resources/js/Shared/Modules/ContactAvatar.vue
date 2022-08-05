<template>
  <div>
    <avatar :data="data.avatar" :classes="'mx-auto mb-6 sm:w-1/2'" :img-classes="'rounded sm:w-72'" />
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
