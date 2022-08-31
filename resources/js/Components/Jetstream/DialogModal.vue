<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  maxWidth: {
    type: String,
    default: '2xl',
  },
  closeable: {
    type: Boolean,
    default: true,
  },
});

const close = () => {
  emit('close');
};
</script>

<template>
  <Modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
    <div class="px-6 py-4">
      <div class="text-lg">
        <slot name="title" />
      </div>

      <div class="mt-4">
        <slot name="content" />
      </div>
    </div>

    <div class="flex flex-row justify-end bg-gray-100 px-6 py-4 text-right dark:bg-gray-900">
      <slot name="footer" />
    </div>
  </Modal>
</template>
