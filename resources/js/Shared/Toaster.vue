<script setup>
import emitter from 'tiny-emitter/instance';
import { onMounted, ref } from 'vue';

const props = defineProps({
  level: String,
  message: String,
});

const isOpen = ref(false);
const closeAfter = ref(5000); // 10 seconds, you can change that
const levelClass = ref(null);
const messageText = ref(null);

onMounted(() => {
  if (props.message) {
    show(props);
  }

  emitter.on('flash', (data) => show(data));
});

const show = (data) => {
  if (data) {
    messageText.value = data.message;
    levelClass.value = 'is-' + data.level;
  }

  act(true, 100);
  hide();
};

const hide = () => {
  act(false, closeAfter.value);
};

const act = (action, timeout) => {
  setTimeout(() => {
    isOpen.value = action;
  }, timeout);
};
</script>

<template>
  <div
    class="fixed bottom-8 z-9999 rounded-md border-zinc-200 bg-white px-5 py-2.5 shadow-xs shadow-gray-400 transition duration-700 ease-in-out dark:border-zinc-600 dark:bg-gray-900 dark:shadow-gray-600"
    :class="[
      levelClass,
      isOpen ? ['opacity-100', 'translate-x-0', 'end-7'] : ['opacity-0', 'translate-x-full', 'end-0'],
    ]">
    <span class="me-1"> 👋 </span>
    {{ messageText }}
  </div>
</template>
