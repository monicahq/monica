<template>
  <div :class="dclass">
    <div>
      <p-input
        ref="input"
        v-model.lazy="prop"
        :name="name"
        :type="_type"
        :class="inputClass"
        :color="inputColor"
        :value="value"
        :disabled="disabled"
        :required="required"
        @change="$emit('change', $event)"
      >
        <slot></slot>
        <slot slot="extra" name="inputextra"></slot>
      </p-input>
    </div>
    <div class="pointer" @click="select()">
      <label v-if="hasSlot('label')" class="pointer">
        <slot name="label"></slot>
      </label>
      <slot name="extra"></slot>
    </div>
  </div>
</template>

<script>
import PInput from 'pretty-checkbox-vue/input';

export default {

  components: {
    PInput
  },

  model: {
    prop: 'modelValue',
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
    modelValue: {
      type: [String, Boolean],
      default: '',
    },
    iclass: {
      type: [String, Array],
      default: ''
    },
    fullClass: {
      type: [String, Array],
      default: ''
    },
    dclass: {
      type: [String, Array],
      default: ''
    },
    color: {
      type: [String, Array],
      default: ''
    },
    disabled: {
      type: Boolean,
      default: false
    },
    required: {
      type: Boolean,
      default: false
    }
  },

  data() {
    return {
      prop: null
    };
  },

  computed: {
    _type() {
      if (this.$options.input_type) {
        return this.$options.input_type;
      }
      return 'input';
    },
    inputClass() {
      return this.fullClass !== '' ? this.fullClass : [this.iclass, 'p-default', this.$options.input_iclass];
    },
    inputColor() {
      return this.color !== '' ? this.color : 'primary-o';
    },
  },

  watch: {
    modelValue(val) {
      this.prop = val;
    },
  },

  mounted() {
    this.prop = this.modelValue;
  },

  methods: {
    select() {
      if (this.disabled) {
        return;
      }
      switch (this._type)
      {
      case 'checkbox':
        this.$refs.input.$refs.input.checked = ! this.$refs.input.$refs.input.checked;
        this.$emit('change', this.$refs.input.$refs.input.checked);
        break;
      case 'radio':
        this.$refs.input.$refs.input.checked = true;
        this.$emit('change', this.value);
        break;
      case 'input':
          //this.$refs.input.$refs.input.focus();
      }
    },
    hasSlot (name = 'default') {
      return !!this.$slots[ name ] || !!this.$scopedSlots[ name ];
    }
  }
};
</script>
