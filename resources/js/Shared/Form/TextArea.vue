<template>
  <div class="mb3">
    <label v-if="label" class="mb-2 block text-sm dark:text-gray-100" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge rounded px-[3px] py-px text-xs">
        {{ $t('optional') }}
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
      <span
        v-if="maxlength && displayMaxLength"
        class="length absolute end-2.5 top-2.5 rounded px-1 py-[3px] text-xs dark:text-gray-100">
        {{ charactersLeft }}
      </span>
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
    markdown: {
      type: Boolean,
      default: false,
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
