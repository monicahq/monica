<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  id: {
    type: String,
    default: 'dropdown-',
  },
  data: Object,
  dropdownClass: String,
  modelValue: {
    type: [String, Number],
    default: '',
  },
  name: {
    type: String,
    default: 'input',
  },
  help: String,
  label: String,
  required: Boolean,
  disabled: Boolean,
  autocomplete: String,
  placeholder: String,
});

const emit = defineEmits(['esc-key-pressed', 'update:modelValue']);

const input = ref(null);

const localDropdownClasses = computed(() => {
  return [
    'py-2 px-3 ps-2 pe-5 ltr:bg-[right_3px_center] rtl:bg-[left_3px_center] rounded-md shadow-sm sm:text-sm',
    'bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700',
    'focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 focus:outline-none',
    props.dropdownClass,
  ];
});

const localData = computed(() => {
  return _.map(props.data, (value) => {
    if (_.isObject(value)) {
      return value;
    } else {
      return {
        id: value,
        name: value,
      };
    }
  });
});

const sendEscKey = () => {
  emit('esc-key-pressed');
};

const change = (event) => {
  emit('update:modelValue', event.target.value);
};

defineExpose({
  focus: () => {
    input.value.focus();
  },
});
</script>

<template>
  <div>
    <label v-if="label" class="mb-2 block text-sm" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge rounded px-[3px] py-px text-xs">
        {{ $t('optional') }}
      </span>
    </label>

    <div class="component relative">
      <select
        :id="id"
        ref="input"
        :value="modelValue"
        :class="localDropdownClasses"
        :required="required"
        :disabled="disabled"
        :placeholder="placeholder"
        @keydown.esc="sendEscKey"
        @change="change">
        <template v-for="item in localData" :key="item.id">
          <optgroup v-if="item.optgroup" :label="item.optgroup">
            <option v-for="option in item.options" :key="option.id" :value="option.id">
              {{ option.name }}
            </option>
          </optgroup>
          <option v-else :value="item.id">
            {{ item.name }}
          </option>
        </template>
      </select>
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
