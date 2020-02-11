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
      class="mb2"
      :class="{ b: required }"
    >
      {{ title }}
    </label>
    <input
      :id="id"
      ref="input"
      :type="type"
      :required="required"
      :name="id"
      :placeholder="placeholder"
      :class="classes"
      :value="value"
      :maxlength="maxlength"
      @input="onInput($event)"
      @blur="onBlur($event)"
      @change="onChange($event)"
      @keyup.enter="onSubmit($event)"
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
    customClass: {
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
  },

  data() {
    return {
      classes: '',
    };
  },

  created: function() {
    this.classes = this.customClass != '' ? this.customClass : 'br2 f5 w-100 ba b--black-40 pa2 outline-0';
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
  },

};
</script>
