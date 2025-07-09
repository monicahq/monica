<script>
export default {
  inheritAttrs: false,
};
</script>

<script setup>
import { computed, onMounted, onUnmounted, ref, useAttrs, useTemplateRef } from 'vue';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
  modelValue: [String, Number],
  options: [Array, Object],
  excludedId: {
    type: Number,
    default: -1,
  },
  align: {
    type: String,
    default: 'right',
  },
  width: {
    type: String,
    default: 'full',
  },
  height: {
    type: String,
    default: '48',
  },
  contentClasses: {
    type: Array,
    default: () => ['py-1', 'bg-white', 'dark:bg-gray-800'],
  },
  inputClasses: {
    type: Array,
    default: () => ['p-1', 'px-2', 'appearance-none', 'outline-hidden', 'w-full', 'dark:bg-gray-900'],
  },
});

const main = ref(null);
const select = useTemplateRef('select');
const open = ref(false);

const proxySelect = computed({
  get() {
    const id = props.options.findIndex((x) => x.id === props.modelValue);
    return id > -1 ? props.options[id]['name'] : input.value;
  },

  set(val) {
    const id = props.options.findIndex((x) => x.name === val);
    emit('update:modelValue', id > -1 ? props.options[id]['id'] : '');
  },
});

const input = ref(null);

const filtered = computed(() => {
  return input.value !== null
    ? props.options.filter((option) => option.name.search(new RegExp(input.value, 'i')) > -1)
    : props.options.filter((option) => option.id !== props.excludedId);
});

defineExpose({
  focus: () => {
    select.value.focus();
    open.value = true;
  },
});

const onKeydown = (e) => {
  if (open.value && e.key === 'Escape') {
    close();
  }
};
const close = () => {
  open.value = false;
  input.value = null;
};

onMounted(() => {
  document.addEventListener('keydown', onKeydown);
  if (_.find(useAttrs(), (item, key) => key === 'autofocus') > -1) {
    nextTick().then(() => {
      select.value.focus();
      open.value = true;
    });
  }
});
onUnmounted(() => document.removeEventListener('keydown', onKeydown));

const widthClass = computed(() => {
  return {
    48: 'w-48',
    full: 'w-full',
  }[props.width.toString()];
});

const heightClass = computed(() => {
  return {
    48: 'max-h-48',
    full: 'max-h-full',
  }[props.height.toString()];
});

const alignmentClasses = computed(() => {
  if (props.align === 'left') {
    return 'origin-top-left left-0';
  }

  if (props.align === 'right') {
    return 'origin-top-right right-0';
  }

  return 'origin-top';
});
</script>

<template>
  <div ref="main" class="relative" :class="$attrs.class">
    <div
      class="flex rounded-md border border-gray-300 p-1 shadow-xs focus-within:border-indigo-300 focus-within:ring-3 focus-within:ring-indigo-200/50 dark:border-gray-600 dark:shadow-gray-700">
      <input
        v-model="proxySelect"
        :id="$attrs.id"
        :autocomplete="$attrs.autocomplete"
        ref="select"
        :class="inputClasses"
        @focus="open = true" />
      <div>
        <button
          @click.prevent="
            proxySelect = '';
            close();
          "
          class="flex h-full w-6 cursor-pointer items-center text-gray-400 outline-hidden focus:outline-hidden dark:text-gray-600">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="100%"
            height="100%"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="feather feather-x h-4 w-4">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <div class="flex w-8 items-center border-s border-gray-200 py-1 pe-1 ps-2 text-gray-300 dark:border-gray-600">
        <button
          @click.prevent="
            open = !open;
            if (open) {
              select.focus();
            }
          "
          class="h-6 w-6 cursor-pointer text-gray-600 outline-hidden focus:outline-hidden dark:text-gray-300">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="100%"
            height="100%"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="feather feather-chevron-up h-4 w-4">
            <polyline v-if="open" points="18 15 12 9 6 15"></polyline>
            <polyline v-else points="18 9 12 15 6 9"></polyline>
          </svg>
        </button>
      </div>
    </div>

    <!-- Full Screen Overlay -->
    <div v-show="open" class="fixed inset-0 z-40" @click="close" />

    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95">
      <div
        v-show="open"
        class="absolute z-50 mt-2 overflow-auto rounded-md shadow-lg dark:shadow-gray-700"
        :class="[widthClass, heightClass, alignmentClasses]">
        <div class="rounded-md ring-1 ring-black/5" :class="contentClasses">
          <div
            v-for="option in filtered"
            :key="option.id"
            class="w-full cursor-pointer rounded-t border-b border-gray-100 hover:bg-teal-100 dark:border-gray-900 dark:bg-gray-800"
            @click="
              proxySelect = option.name;
              close();
            ">
            <div
              class="relative flex w-full items-center border-s-2 border-transparent bg-white p-2 ps-2 hover:border-teal-600 hover:bg-teal-600 hover:text-teal-100 dark:bg-gray-800 dark:hover:border-teal-400 dark:hover:bg-teal-400 dark:hover:text-teal-900">
              <div class="flex w-full items-center">
                <div class="mx-2 leading-6">
                  {{ option.name }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>
