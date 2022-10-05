<template>
  <div class="mb3">
    <label v-if="label" class="mb-2 block text-sm dark:text-gray-100" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge text-xs">
        {{ $t('app.optional') }}
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
      <span v-if="maxlength && displayMaxLength" class="length absolute rounded text-xs dark:text-gray-100">
        {{ charactersLeft }}
      </span>
    </div>

    <p v-if="help" class="f7 mb3 lh-title">
      {{ help }}
    </p>
  </div>
</template>

<script>
export default {
  inheritAttrs: false,

  model: {
    prop: 'modelValue',
    event: 'update:modelValue',
  },

  props: {
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
  },

  emits: ['esc-key-pressed', 'update:modelValue'],

  data() {
    return {
      displayMaxLength: false,
    };
  },

  computed: {
    proxyValue: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit('update:modelValue', value);
      },
    },

    charactersLeft() {
      var char = 0;
      if (this.modelValue) {
        char = this.modelValue.length;
      }

      return `${this.maxlength - char} / ${this.maxlength}`;
    },

    localTextAreaClasses() {
      return [
        'rounded-md shadow-sm',
        'bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-700',
        'focus:border-indigo-300 focus:dark:border-indigo-700 focus:ring focus:ring-indigo-200 focus:dark:ring-indigo-800 focus:ring-opacity-50',
        this.textareaClass,
      ];
    },
  },

  methods: {
    sendEscKey() {
      this.$emit('esc-key-pressed');
    },

    showMaxLength() {
      this.displayMaxLength = true;
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
