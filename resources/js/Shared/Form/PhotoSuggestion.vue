<script setup>
import { ref } from 'vue';

defineEmits(['update:modelValue']);

const props = defineProps({
  modelValue: String,
  photos: Array,
});

const selected = ref({ src: '' });

const select = (index) => {
  selected.value = props.photos[index];
  emit('update:modelValue', selected);
};
</script>

<template>
  <div>
    <a v-for="(photo, index) in photos" :key="index" @click.prevent="select(index)">
      <img class="h-auto rounded sm:w-72" :src="photo.src" :alt="photo.alt ?? ''" />
    </a>
  </div>
</template>
