<template>
  <div class="">
    <div v-if="data.type === 'svg'" :class="classes" v-html="data.content" />
    <img v-else :class="classes + ' ' + imgClasses" :src="data.content" alt="avatar" />
  </div>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      default: null,
    },
    classes: {
      type: String,
      default: null,
    },
    imgClasses: {
      type: String,
      default: '',
    },
  },
};
</script>
