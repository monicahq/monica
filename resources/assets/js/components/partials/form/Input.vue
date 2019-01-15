<style scoped>
input {
  transition: all;
  transition-duration: 0.2s;
  border: 1px solid #c4cdd5;
}
input:focus {
  border: 1px solid #5c6ac4;
}
</style>

<template>
  <div>
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
      :class="inputClass"
      :style="inputStyle"
      :value="value"
      @input="event => { $emit('input', event.target.value) }"
    />
  </div>
</template>

<script>
export default {

  props: {
    value: {
      type: String,
      default: '',
    },
    title: {
      type: String,
      default: '',
    },
    id: {
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
      type: String,
      default: ''
    },
  },

  computed: {
    realid() {
      return this.id + this._uid;
    },
    inputClass() {
      return this.iclass != '' ? this.iclass : 'br2 f5 w-100 ba b--black-40 pa2 outline-0';
    },
    inputStyle() {
      return this.width >= 0 ? 'width:' + this.width + 'px' : '';
    }
  }

};
</script>
