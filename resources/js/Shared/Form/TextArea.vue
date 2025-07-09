<script>
export default {
  inheritAttrs: false,
};
</script>

<script setup>
import { computed, ref, useTemplateRef } from 'vue';

const props = defineProps({
  id: {
    type: String,
    default: 'text-area-',
  },
  type: {
    type: String,
    default: 'text',
  },
  textareaClass: {
    type: String,
    default: '',
  },
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  help: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  rows: {
    type: Number,
    default: 3,
  },
  autofocus: {
    type: Boolean,
    default: false,
  },
  maxlength: {
    type: Number,
    default: null,
  },
  markdown: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['esc-key-pressed', 'update:modelValue']);

const displayMaxLength = ref(false);
const zone = useTemplateRef('zone');

const proxyValue = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    emit('update:modelValue', value);
  },
});

const charactersLeft = computed(() => {
  let char = 0;
  if (props.modelValue) {
    char = props.modelValue.length;
  }

  return `${props.maxlength - char} / ${props.maxlength}`;
});

const localTextAreaClasses = computed(() => [
  'rounded-md shadow-xs',
  'bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-700',
  'focus:border-indigo-300 dark:focus:border-indigo-700 focus:ring-3 focus:ring-indigo-200 dark:focus:ring-indigo-800/50',
  props.textareaClass,
]);

const sendEscKey = () => {
  emit('esc-key-pressed');
};

const showMaxLength = () => {
  displayMaxLength.value = true;
};

const focus = () => {
  zone.value.focus();
};

defineExpose({
  focus,
});
</script>

<template>
  <div>
    <label v-if="label" class="mb-2 block relative text-sm dark:text-gray-100" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge rounded-xs px-[3px] py-px text-xs">
        {{ $t('optional') }}
      </span>

      <span
        v-if="maxlength && displayMaxLength"
        class="length absolute end-0 top-0 rounded-xs px-1 py-[3px] text-xs dark:text-gray-100">
        {{ charactersLeft }}
      </span>
    </label>

    <div class="relative">
      <textarea
        :id="id"
        v-model="proxyValue"
        :class="localTextAreaClasses"
        :required="required"
        :type="type"
        :autofocus="autofocus"
        :rows="rows"
        ref="zone"
        :maxlength="maxlength"
        @input="$emit('update:modelValue', $event.target.value)"
        @keydown.esc="sendEscKey"
        @focus="showMaxLength"
        @blur="displayMaxLength = false" />
    </div>
    <p v-if="markdown" class="rounded-b-lg bg-slate-100 px-3 py-2 text-xs dark:bg-slate-900">
      <span>{{ $t('We support Markdown to format the text (bold, lists, headings, etc…).') }}</span>

      <a
        href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet"
        target="_blank"
        lang="en"
        rel="noopener noreferrer"
        class="ms-1 text-blue-500 hover:underline">
        {{ $t('(Help)') }}
      </a>
    </p>

    <p v-if="help" class="f7 mb3 lh-title">
      {{ help }}
    </p>
  </div>
</template>

<style lang="scss" scoped>
.optional-badge {
  color: #283e59;
  background-color: #edf2f9;
}
.dark .optional-badge {
  color: #d4d8dd !important;
  background-color: #2f3031 !important;
}

.length {
  background-color: #e5eeff;
}
.dark .length {
  background-color: #2d2f33 !important;
}
</style>
