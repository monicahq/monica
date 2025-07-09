<script setup>
import { computed, onMounted, onUnmounted, ref, useTemplateRef, watch } from 'vue';

const props = defineProps({
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

const emit = defineEmits(['close']);
const dialog = useTemplateRef('dialog');
const showSlot = ref(props.show);

watch(
  () => props.show,
  () => {
    if (props.show) {
      document.body.style.overflow = 'hidden';
      showSlot.value = true;
      dialog.value?.showModal();
    } else {
      document.body.style.overflow = null;
      setTimeout(() => {
        dialog.value?.close();
        showSlot.value = false;
      }, 200);
    }
  },
);

const close = () => {
  if (props.closeable) {
    emit('close');
  }
};

const closeOnEscape = (e) => {
  if (e.key === 'Escape') {
    e.preventDefault();

    if (props.show) {
      close();
    }
  }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape);
  document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
  return {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
  }[props.maxWidth];
});
</script>

<template>
  <dialog
    class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent margin-auto"
    ref="dialog">
    <div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50" scroll-region>
      <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
          <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75" />
        </div>
      </transition>

      <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        <div
          v-show="show"
          class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full sm:mx-auto"
          :class="maxWidthClass">
          <slot v-if="showSlot" />
        </div>
      </transition>
    </div>
  </dialog>
</template>
