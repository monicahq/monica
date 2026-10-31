<template>
  <div>
    <avatar :data="data.avatar" :class="'mx-auto mb-6 w-32 sm:w-72'" :img-classes="'rounded-full'" />
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
