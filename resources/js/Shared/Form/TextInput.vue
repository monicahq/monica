<script setup>
import { uniqueId } from 'lodash';
import { computed, onMounted, ref, useTemplateRef } from 'vue';

const props = defineProps({
  id: {
    type: String,
    default: null,
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
const input = useTemplateRef('input');

const realId = computed(() => {
  return props.id ?? uniqueId('text-input-');
});

const charactersLeft = computed(() => {
  let char = 0;
  if (props.modelValue) {
    char = props.modelValue.length;
  }

  return `${props.maxlength - char} / ${props.maxlength}`;
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
    <label v-if="label" class="mb-2 block text-sm dark:text-gray-100" :for="realId">
      {{ label }}
      <span v-if="!required" class="optional-badge rounded-xs px-[3px] py-px text-xs">
        {{ $t('optional') }}
      </span>
    </label>

    <div class="relative">
      <input
        :id="realId"
        ref="input"
        :class="[
          'rounded-md shadow-xs',
          'bg-white dark:bg-slate-900 dark:text-gray-100 border-gray-300 dark:border-gray-700',
          'placeholder:text-gray-600 dark:placeholder:text-gray-400',
          'focus:border-indigo-300 dark:focus:border-indigo-700 focus:ring-3 focus:ring-indigo-200 dark:focus:ring-indigo-800/50',
          'disabled:bg-slate-50 dark:disabled:bg-slate-700',
          props.inputClass,
        ]"
        :value="modelValue"
        :type="type"
        :name="name"
        :maxlength="maxlength"
        :required="required"
        :autofocus="autofocus"
        :autocomplete="typeof autocomplete === 'string' ? autocomplete : autocomplete ? '' : 'off'"
        :disabled="disabled ? 'disabled' : null"
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
        class="length absolute end-2.5 top-2.5 rounded-xs px-1 py-[3px] text-xs dark:text-gray-100">
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
