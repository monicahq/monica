<style scoped>
</style>

<template>
  <div :class="dclass">
    <div>
      <p-radio
        ref="input"
        v-model.lazy="checked"
        :name="name"
        :class="inputClass"
        :color="inputColor"
        :value="value"
        :disabled="disabled"
        @change="event => { $emit('change', event) }"
      >
        <slot></slot>
      </p-radio>
    </div>
    <div class="pointer" @click="select()">
      <label class="pointer" v-if="hasSlot('label')" :for="formid">
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
      return [this.iclass, 'p-default p-round p-thick'];
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
      this.$refs.input.$refs.input.checked = true;
      this.$emit('change', this.value);
    },
    hasSlot (name = 'default') {
      return !!this.$slots[ name ] || !!this.$scopedSlots[ name ];
    }
  }
};
</script>
