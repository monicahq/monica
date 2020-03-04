<style scoped>
.input {
  transition: all;
  transition-duration: 0.2s;
  border: 1px solid #c4cdd5;
}

.input:focus {
  border: 1px solid #5c6ac4;
}
</style>

<template>
  <div>
    <label
      v-if="title"
      :for="name"
      :class="customLabelClasses"
    >
      {{ title }}
      <span v-if="!required">Optional</span>
    </label>
    <input
      :id="id"
      ref="input"
      :type="type"
      :required="required"
      :name="id"
      :placeholder="placeholder"
      :class="customInputClasses"
      :value="value"
      :maxlength="maxlength"
      :min="min"
      :max="max"
      @input="onInput($event)"
      @blur="onBlur($event)"
      @change="onChange($event)"
      @keyup.enter="onSubmit($event)"
      @keyup.esc="onEscape($event)"
    />
  </div>
</template>

<script>
export default {

  props: {
    value: {
      type: [String, Number],
      default: '',
    },
    title: {
      type: String,
      default: '',
    },
    label: {
      type: String,
      default: null,
    },
    name: {
      type: String,
      default: '',
    },
    placeholder: {
      type: String,
      default: '',
    },
    required: {
      type: Boolean,
      default: true,
    },
    type: {
      type: String,
      default: '',
    },
    id: {
      type: String,
      default: '',
    },
    labelClass: {
      type: String,
      default: '',
    },
    inputClass: {
      type: String,
      default: '',
    },
    width: {
      type: Number,
      default: -1,
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
  },

  data() {
    return {
      customInputClasses: '',
      customLabelClasses: '',
    };
  },

  created: function() {
    this.customInputClasses = this.inputClass + ' ' + this.customInputClasses;
    this.customLabelClasses = this.labelClass + ' ' + this.customLabelClasses;
    if (this.required) {
      this.customLabelClasses = this.customLabelClasses + ' b';
    }
  },

  methods: {
    focus() {
      this.$refs.input.focus();
    },

    onInput(event) {
      this.$emit('input', event.target.value);
    },

    onSubmit(event) {
      this.$emit('submit', event.target.value);
    },

    onBlur(event) {
      this.$emit('blur', event.target.value);
    },

    onChange(event) {
      this.$emit('change', event.target.value);
    },

    onEscape(event) {
      this.$emit('escape');
    },
  },

};
</script>
