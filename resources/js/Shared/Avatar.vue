<script setup>
defineProps({
  data: Object,
  imgClasses: String,
});
</script>

<template>
  <div v-if="data.type === 'svg'" v-html="data.content" />
  <img v-else-if="data.type === 'dicebear'" :src="data.content" alt="avatar" />
  <img v-else :class="imgClasses" :src="data.content" alt="avatar" />
</template>
