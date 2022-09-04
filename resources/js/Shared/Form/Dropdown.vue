<template>
  <div :class="divOuterClass">
    <label v-if="label" class="mb-2 block text-sm" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge text-xs"> optional </span>
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
        <option v-for="item in data" :key="item.id" :value="item.id">
          {{ item.name }}
        </option>
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
      type: Array,
      default() {
        return [];
      },
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
        'py-2 px-3 rounded-md shadow-sm sm:text-sm',
        'bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700',
        'focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 focus:outline-none',
        this.dropdownClass,
      ];
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
  border-radius: 4px;
  color: #283e59;
  background-color: #edf2f9;
  padding: 1px 3px;
}

@media (prefers-color-scheme: dark) {
  .optional-badge {
    color: #d4d8dd;
    background-color: #2f3031;
  }
}

.counter {
  padding-right: 64px;
}

select {
  padding-left: 8px;
  padding-right: 20px;
  background-position: right 3px center;
}
</style>
