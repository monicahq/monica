<template>
  <div :class="divOuterClass">
    <label v-if="label" class="mb-2 block text-sm dark:text-gray-100" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge text-xs">
        {{ $t('app.optional') }}
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
        :autocomplete="autocomplete ? '' : 'off'"
        :disabled="disabled"
        :min="min"
        :max="max"
        :step="step"
        :placeholder="placeholder"
        @input="$emit('update:modelValue', $event.target.value)"
        @keydown.esc="sendEscKey"
        @focus="displayMaxLength = true"
        @blur="displayMaxLength = false" />
      <span v-if="maxlength && displayMaxLength" class="length absolute rounded text-xs dark:text-gray-100">
        {{ charactersLeft }}
      </span>
    </div>

    <p v-if="help" class="mb-3 mt-1 text-xs">
      {{ help }}
    </p>
  </div>
</template>

<script>
export default {
  props: {
    id: {
      type: String,
      default: 'text-input-',
    },
    inputClass: {
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
    type: {
      type: String,
      default: 'text',
    },
    name: {
      type: String,
      default: 'input',
    },
    placeholder: {
      type: String,
      default: '',
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
    autofocus: {
      type: Boolean,
      default: false,
    },
    autocomplete: {
      type: Boolean,
      default: true,
    },
    maxlength: {
      type: Number,
      default: null,
    },
    min: {
      type: Number,
      default: null,
    },
    max: {
      type: Number,
      default: null,
    },
    step: {
      type: String,
      default: 'any',
    },
  },
  emits: ['esc-key-pressed', 'update:modelValue'],

  data() {
    return {
      displayMaxLength: false,
    };
  },

  computed: {
    charactersLeft() {
      var char = 0;
      if (this.modelValue) {
        char = this.modelValue.length;
      }

      return `${this.maxlength - char} / ${this.maxlength}`;
    },

    localInputClasses() {
      return [
        'rounded-md shadow-sm',
        'bg-white dark:bg-slate-900 dark:text-gray-100 border-gray-300 dark:border-gray-700',
        'placeholder:text-gray-600 placeholder:dark:text-gray-400',
        'focus:border-indigo-300 focus:dark:border-indigo-700 focus:ring focus:ring-indigo-200 focus:dark:ring-indigo-800 focus:ring-opacity-50 focus:dark:ring-opacity-900',
        'disabled:bg-slate-50 disabled:dark:bg-slate-900',
        this.inputClass,
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

.length {
  top: 10px;
  right: 10px;
  background-color: #e5eeff;
  padding: 3px 4px;
}

@media (prefers-color-scheme: dark) {
  .optional-badge {
    color: #d4d8dd;
    background-color: #2f3031;
  }
  .length {
    background-color: #2d2f33;
  }
}

.counter {
  padding-right: 64px;
}
</style>
