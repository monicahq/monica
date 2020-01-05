<style scoped>
input {
  transition: all;
  transition-duration: 0.2s;
  border: 1px solid #c4cdd5;
}

input:focus {
  border: 1px solid #5c6ac4;
}

.error {
  color:#f57f6c;
}

input.error {
  border-color: #f79483;
}

.form-group-error {
  color:#f57f6c;

  animation-name: shake;
  animation-fill-mode: forwards;
  animation-duration: .6s;
  animation-timing-function: ease-in-out;
}

@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }

  15%, 45%, 75% {
    transform: translateX(0.375rem);
  }

  30%, 60%, 90% {
    transform: translateX(-0.375rem);
  }
}

</style>

<template>
  <div :class="{ 'form-group-error': validator && validator.$error }">
    <label
      v-if="title"
      :for="realid"
      class="mb2"
      :class="{ b: required }"
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
      @input="onInput($event)"
      @keyup.enter="onSubmit($event)"
    />
    <small v-if="validator && (validator.$error && !validator.required)">
      {{ requiredMessage }}
    </small>
    <small v-if="validator && (validator.$error && !validator.maxLength)">
      {{ maxLengthMessage }}
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
    name: {
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
    }
  },

  computed: {
    realid() {
      return this.id + this._uid;
    },
    inputClass() {
      var c = [this.iclass != '' ? this.iclass : 'br2 f5 w-100 ba b--black-40 pa2 outline-0'];
      if (this.validator) {
        c.push({ 'error': this.validator.$error });
      }
      return c;
    },
    inputStyle() {
      return this.width >= 0 ? 'width:' + this.width + 'px' : '';
    },

    fieldName() {
      return this.name && this.name.length > 0 ? this.name : this.title;
    },
    requiredMessage() {
      return this.$t('validation.vue.required', { field: this.fieldName })
    },
    maxLengthMessage() {
      var type = 'string';
      switch (this.inputType) {
        case 'number':
          type = 'numeric';
          break;
      }
      return this.$t('validation.vue.max.'.type, {
        field: this.fieldName,
        max: this.validator.$params.maxLength.max,
      });
    },
  },

  methods: {
    focus() {
      this.$refs.input.focus();
    },

    onInput(event) {
      if (this.validator) {
        this.validator.$touch();
      }
      this.$emit('input', this._parseInput(event.target.value));
    },

    onSubmit(event) {
      if (this.validator) {
        this.validator.$touch();
      }
      this.$emit('submit', this._parseInput(event.target.value));
    },

    _parseInput(value) {
      switch (this.inputType) {
        case 'number':
          return parseInt(value);
          break;
      }
      return value;
    }
  },

};
</script>
