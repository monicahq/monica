<script setup>
const emit = defineEmits(['update:modelValue', 'select']);

const props = defineProps({
  modelValue: Object,
  photos: Array,
});

const select = (selectedIndex) => {
  props.photos.map(function (photo, index) {
    photo.selected = selectedIndex === index;
    if (photo.selected) {
      emit('update:modelValue', photo);
      emit('select', photo);
    }
    return photo;
  });
};
</script>

<template>
  <div :key="index" v-for="(photo, index) in photos">
    <a @click.prevent="select(index)" href="javascript:void(0)">
      <img class="h-auto rounded sm:w-72" :src="photo.src" :alt="photo.alt ?? ''" />
    </a>
  </div>
</template>
