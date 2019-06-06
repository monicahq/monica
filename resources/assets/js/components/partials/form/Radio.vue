<style scoped>
</style>

<template>
  <div :class="dclass">
    <div>
      <p-radio
        ref="input"
        :name="name"
        :class="inputClass"
        :color="inputColor"
        :value="value"
        :disabled="disabled"
        v-model="vmodel"
        @change="event => { $emit('change', event) }"
      >
        <slot></slot>
      </p-radio>
    </div>
    <div class="pointer" @click="select()">
      <label :for="formid">
        <slot name="label"></slot>
      </label>
      <slot name="extra"></slot>
    </div>
  </div>
</template>

<script>
import PRadio from 'pretty-checkbox-vue/radio';

export default {

  components: {
    PRadio
  },

  model: {
    prop: 'model',
    event: 'change'
  },

  props: {
    name: {
      type: String,
      default: '',
    },
    value: {
      type: String,
      default: '',
    },
    model: {
      type: String,
      default: '',
    },
    iclass: {
      type: String,
      default: ''
    },
    dclass: {
      type: String,
      default: ''
    },
    color: {
      type: String,
      default: ''
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },

  data() {
    return {
      vmodel: null,
    };
  },

  mounted() {
    this.vmodel = this.model;
  },

  computed: {
    inputClass() {
      return this.iclass != '' ? this.iclass : 'p-default p-round p-thick';
    },
    inputColor() {
      return this.color != '' ? this.color : 'primary-o';
    },
  },

  methods: {
    formid() {
      return this.$refs.input.id;
    },
    select() {
      this.vmodel = this.value;
      this.$refs.input.$refs.input.checked = true;
      this.$emit('change', this.value);
    }
  }
};
</script>
