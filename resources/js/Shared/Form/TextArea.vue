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

.counter {
  padding-right: 64px;
}
</style>

<template>
  <div class="mb3">
    <label v-if="label" class="mb-2 block text-sm" :for="id">
      {{ label }}
      <span v-if="!required" class="optional-badge text-xs"> {{ $t('app.optional') }} </span>
    </label>

    <div class="relative">
      <textarea
        :id="id"
        :ref="ref"
        v-model="proxyValue"
        :class="localTextAreaClasses"
        :required="required"
        :type="type"
        :autofocus="autofocus"
        :rows="rows"
        :maxlength="maxlength"
        @input="$emit('update:modelValue', $event.target.value)"
        @keydown.esc="sendEscKey"
        @focus="showMaxLength"
        @blur="displayMaxLength = false" />
      <span v-if="maxlength && displayMaxLength" class="length absolute rounded text-xs">
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
    ref: {
      type: String,
      default: 'textarea',
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
  },

  created() {
    this.localTextAreaClasses =
      'border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 rounded-md shadow-sm ' +
      this.textareaClass;
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
