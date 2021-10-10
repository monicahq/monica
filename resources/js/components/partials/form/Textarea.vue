<style scoped>
textarea {
  transition: all;
  transition-duration: 0.2s;
  border: 1px solid #c4cdd5;
}
textarea:focus {
  border: 1px solid #5c6ac4;
}
</style>

<template>
  <div>
    <label
      v-if="label"
      :for="realid"
      class="mb2"
      :class="{ b: required }"
    >
      {{ label }}
    </label>
    <textarea
      :id="realid"
      v-model="buffer"
      autofocus
      :required="required"
      :name="id"
      :placeholder="placeholder"
      :rows="rows"
      class="br2 f5 w-100 ba b--black-40 pa2 outline-0"
      :style="textareaStyle"
      @input="$emit('input', buffer)"
    ></textarea>
  </div>
</template>

<script>
export default {

  props: {
    value: {
      type: String,
      default: '',
    },
    label: {
      type: String,
      default: '',
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
    width: {
      type: Number,
      default: -1,
    },
    rows: {
      type: Number,
      default: 0,
    }
  },

  data() {
    return {
      buffer: this.value
    };
  },

  computed: {
    realid() {
      return this.id + this._uid;
    },
    textareaStyle() {
      return this.width >= 0 ? 'width:' + this.width + 'px' : '';
    }
  },

  watch: {
    value: function (newValue) {
      this.buffer = newValue;
    }
  },

  mounted() {
    this.buffer = this.value;
  },
};
</script>
