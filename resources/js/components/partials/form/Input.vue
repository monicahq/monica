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
  <div :class="{ 'form-group-error': validator && validator.$error }">
    <label
      v-if="title"
      :for="realid"
      class="mb2"
      :class="{ b: required, error: validator && validator.$error }"
    >
      {{ title }}
    </label>
    <input
      :id="realid"
      ref="input"
      :type="inputType"
      autofocus
      :required="required"
      :name="id"
      :placeholder="placeholder"
      :class="inputClass"
      :style="inputStyle"
      :value="value"
      :maxlength="maxlength"
      :step="step"
      @input="onInput($event)"
      @blur="onBlur($event)"
      @change="onChange($event)"
      @keyup.enter="onSubmit($event)"
    />
    <small v-if="validator && (validator.$error && validator.required !== undefined && !validator.required)" class="error">
      {{ requiredMessage }}
    </small>
    <small v-if="validator && (validator.$error && validator.maxLength !== undefined && !validator.maxLength)" class="error">
      {{ maxLengthMessage }}
    </small>
    <small v-if="validator && (validator.$error && validator.url !== undefined && !validator.url)" class="error">
      {{ urlMessage }}
    </small>
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
    id: {
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
    inputType: {
      type: String,
      default: '',
    },
    step: {
      type: String,
      default: '',
    },
    width: {
      type: Number,
      default: -1,
    },
    iclass: {
      type: [String, Array],
      default: ''
    },
    maxlength: {
      type: Number,
      default: null,
    },
    validator: {
      type: Object,
      default: null,
    },
  },

  computed: {
    realid() {
      return this.id + this._uid;
    },
    inputClass() {
      var c = [this.iclass != '' ? this.iclass : 'br2 f5 w-100 ba b--black-40 pa2 outline-0'];
      if (this.validator) {
        c.push({ error: this.validator.$error });
      }
      c.push('input');
      return c;
    },
    inputStyle() {
      return this.width >= 0 ? 'width:' + this.width + 'px' : '';
    },

    field() {
      return this.label && this.label.length > 0 ? this.label : this.title;
    },
    requiredMessage() {
      return this.$t('validation.vue.required', { field: this.field });
    },
    urlMessage() {
      return this.$t('validation.vue.url', { field: this.field });
    },
    maxLengthMessage() {
      var type = 'string';
      switch (this.inputType) {
      case 'number':
        type = 'numeric';
        break;
      }
      return this.$t('validation.vue.max.'.type, {
        field: this.field,
        max: this.validator ? this.validator.$params.maxLength.max : '',
      });
    },
  },

  methods: {
    focus() {
      this.$refs.input.focus();
    },

    onInput(event) {
      if (this.validator && event.data !== undefined) {
        this.validator.$reset();
      }
      this.$emit('input', event.target.value);
    },

    onSubmit(event) {
      if (this.validator) {
        this.validator.$touch();
      }
      this.$emit('submit', event.target.value);
    },

    onBlur(event) {
      if (this.validator && event.target.value !== '') {
        this.validator.$touch();
      }
      this.$emit('blur', event.target.value);
    },

    onChange(event) {
      if (this.validator) {
        this.validator.$touch();
      }
      this.$emit('change', event.target.value);
    },
  },

};
</script>
