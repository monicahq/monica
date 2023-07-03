<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
  id: {
    type: String,
    default: 'text-input-',
  },
  inputClass: String,
  modelValue: {
    type: [String, Number],
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  name: {
    type: String,
    default: 'input',
  },
  placeholder: String,
  help: String,
  label: String,
  required: Boolean,
  disabled: Boolean,
  autofocus: Boolean,
  autocomplete: {
    type: [String, Boolean],
    default: '',
  },
  maxlength: Number,
  min: Number,
  max: Number,
  step: {
    type: String,
    default: 'any',
  },
});
const emit = defineEmits(['esc-key-pressed', 'update:modelValue']);

const displayMaxLength = ref(false);
const input = ref(null);

const charactersLeft = computed(() => {
  let char = 0;
  if (props.modelValue) {
    char = props.modelValue.length;
  }

  return `${props.maxlength - char} / ${props.maxlength}`;
});

const localInputClasses = computed(() => {
  return [
    'rounded-md shadow-sm',
    'bg-white dark:bg-slate-900 dark:text-gray-100 border-gray-300 dark:border-gray-700',
    'placeholder:text-gray-600 placeholder:dark:text-gray-400',
    'focus:border-indigo-300 focus:dark:border-indigo-700 focus:ring focus:ring-indigo-200 focus:dark:ring-indigo-800 focus:ring-opacity-50 focus:dark:ring-opacity-900',
    'disabled:bg-slate-50 disabled:dark:bg-slate-900',
    props.inputClass,
  ];
});

onMounted(() => {
  if (props.autofocus) {
    focus();
  }
});

const sendEscKey = () => {
  emit('esc-key-pressed');
};
const focus = () => {
  input.value.focus();
};

defineExpose({ focus: focus });
</script>

<template>
  <div>
    <label v-if="label" class="mb-2 block text-sm dark:text-gray-100" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge rounded px-[3px] py-px text-xs">
        {{ $t('optional') }}
      </span>
    </label>

    <div class="relative">
      <input
        :id="id"
        ref="input"
        :class="localInputClasses"
        :value="modelValue"
        :type="type"
        :name="name"
        :maxlength="maxlength"
        :required="required"
        :autofocus="autofocus"
        :autocomplete="typeof autocomplete === 'string' ? autocomplete : autocomplete ? '' : 'off'"
        :disabled="disabled"
        :min="min"
        :max="max"
        :step="step"
        :placeholder="placeholder"
        @input="$emit('update:modelValue', $event.target.value)"
        @keydown.esc="sendEscKey"
        @focus="displayMaxLength = true"
        @blur="displayMaxLength = false" />
      <span
        v-if="maxlength && displayMaxLength"
        class="length absolute end-2.5 top-2.5 rounded px-1 py-[3px] text-xs dark:text-gray-100">
        {{ charactersLeft }}
      </span>
    </div>

    <p v-if="help" class="mb-3 mt-1 text-xs">
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
