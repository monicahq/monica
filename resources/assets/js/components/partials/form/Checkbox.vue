<style scoped>
</style>

<template>
  <div :class="dclass">
    <div>
      <p-check
        ref="input"
        :name="name"
        :class="inputClass"
        :color="inputColor"
        :value="value"
        :disabled="disabled"
        v-model.lazy="checked"
        @change="event => { $emit('change', event) }"
      >
        <slot></slot>
      </p-check>
    </div>
    <div class="pointer" @click="select()">
      <label>
        <slot name="label"></slot>
      </label>
      <slot name="extra"></slot>
    </div>
  </div>
</template>

<script>
import PCheck from 'pretty-checkbox-vue/check';

export default {

  components: {
    PCheck
  },

  model: {
    prop: 'checked',
    event: 'change'
  },

  props: {
    name: {
      type: String,
      default: '',
    },
    value: {
      type: [String, Boolean],
      default: '',
    },
    checked: {
      type: [String, Boolean],
      default: '',
    },
    iclass: {
      type: [String, Array],
      default: ''
    },
    dclass: {
      type: [String, Array],
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

  computed: {
    inputClass() {
      return [this.iclass, 'p-default p-curve p-thick'];
    },
    inputColor() {
      return this.color != '' ? this.color : 'primary-o';
    },
  },

  methods: {
    select() {
      this.$refs.input.$refs.input.checked = ! this.$refs.input.$refs.input.checked;
      this.$emit('change', this.$refs.input.$refs.input.checked);
    }
  }
};
</script>
