<script setup>
import { computed, nextTick, useTemplateRef } from 'vue';
import { concat, uniqueId } from 'lodash';
import { trans } from 'laravel-vue-i18n';
import TextInput from './TextInput.vue';
import Dropdown from './Dropdown.vue';

const props = defineProps({
  id: {
    type: String,
    default: 'dropdown-',
  },
  data: Object,
  dropdownClass: String,
  inputClass: String,
  help: String,
  label: String,
  required: Boolean,
  disabled: Boolean,
  autocomplete: String,
  placeholder: String,
  addNewValueSelection: {
    type: Boolean,
    default: true,
  },
  newValueSelection: {
    type: Object,
    default: () => ({ id: '-1', name: trans('Custom…') }),
  },
});

const modelValue = defineModel('modelValue', {
  type: String,
  default: '',
});
const newValue = defineModel('newValue', {
  type: String,
  default: '',
});
const emit = defineEmits(['esc-key-pressed']);

const inputRef = useTemplateRef('input');

const sendEscKey = () => {
  emit('esc-key-pressed');
};

const change = (event) => {
  emit('update:modelValue', event.target.value);
  if (event.target.value == props.newValueSelection.id) {
    nextTick(() => {
      inputRef.value.focus();
    });
  }
};
const changeInput = (event) => {
  emit('update:newValue', event.target.value);
  if (event.target.value == '') {
    emit('update:modelValue', '');
  }
};

const realId = computed(() => {
  return props.id ?? uniqueId('combobox-');
});

const localData = computed(() => {
  return props.addNewValueSelection ? concat(props.data, props.newValueSelection) : props.data;
});

defineExpose({
  focus: () => {
    input.value.focus();
  },
});
</script>

<template>
  <div>
    <label v-if="label" class="mb-2 block text-sm" :for="realId">
      {{ label }}
      <span v-if="!required" class="optional-badge rounded-xs px-[3px] py-px text-xs">
        {{ $t('optional') }}
      </span>
    </label>

    <div class="component relative">
      <Dropdown
        v-if="modelValue != newValueSelection.id"
        :id="realId"
        v-model="modelValue"
        :required="required"
        :disabled="disabled"
        :placeholder="placeholder"
        :data="localData"
        :autocomplete="autocomplete"
        :dropdown-class="dropdownClass"
        @esc-key-pressed="sendEscKey"
        @change="change" />

      <TextInput
        v-else
        ref="input"
        :id="realId"
        v-model="newValue"
        :required="required"
        :disabled="disabled"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :input-class="inputClass"
        @esc-key-pressed="sendEscKey"
        @change="changeInput" />
    </div>

    <p v-if="help" class="mt-1 text-xs">
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
</style>
