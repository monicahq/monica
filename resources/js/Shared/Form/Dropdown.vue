<template>
  <div :class="divOuterClass">
    <label v-if="label" class="mb-2 block text-sm" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge rounded px-[3px] py-px text-xs">
        {{ $t('optional') }}
      </span>
    </label>

    <div class="component relative">
      <select
        :id="id"
        :value="modelValue"
        :class="localDropdownClasses"
        :required="required"
        :disabled="disabled"
        :placeholder="placeholder"
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

<script>
export default {
  props: {
    id: {
      type: String,
      default: 'dropdown-',
    },
    data: {
      type: Object,
      default: null,
    },
    dropdownClass: {
      type: String,
      default: '',
    },
    divOuterClass: {
      type: String,
      default: '',
    },
    modelValue: {
      type: [String, Number],
      default: '',
    },
    name: {
      type: String,
      default: 'input',
    },
    help: {
      type: String,
      default: '',
    },
    label: {
      type: String,
      default: '',
    },
    required: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    autocomplete: {
      type: String,
      default: '',
    },
    placeholder: {
      type: String,
      default: '',
    },
  },
  emits: ['esc-key-pressed', 'update:modelValue'],

  computed: {
    localDropdownClasses() {
      return [
        'py-2 px-3 ps-2 pe-5 ltr:bg-[right_3px_center] rtl:bg-[left_3px_center] rounded-md shadow-sm sm:text-sm',
        'bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700',
        'focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 focus:outline-none',
        this.dropdownClass,
      ];
    },
    localData() {
      return _.map(this.data, (value) => {
        if (_.isObject(value)) {
          return value;
        } else {
          return {
            id: value,
            name: value,
          };
        }
      });
    },
  },

  methods: {
    focus() {
      this.$refs.input.focus();
    },

    sendEscKey() {
      this.$emit('esc-key-pressed');
    },

    change(event) {
      this.$emit('update:modelValue', event.target.value);
    },
  },
};
</script>

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
