<style scoped>
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
    <toggle-button
      :id="realid"
      :name="id"
      :class="inputClass"
      :sync="true"
      :labels="labels"
      :value="selectedOption"
      @input="event => { $emit('input', event) }"
    />
  </div>
</template>

<script>
export default {

  props: {
    value: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: '',
    },
    labels: {
      type: [Boolean, Object],
      default: false,
    },
    id: {
      type: String,
      default: '',
    },
    required: {
      type: Boolean,
      default: true,
    },
    iclass: {
      type: String,
      default: ''
    },
  },

  data() {
    return {
      selectedOption: null,
    };
  },

  computed: {
    realid() {
      return this.id + this._uid;
    },
    inputClass() {
      return this.iclass != '' ? this.iclass : '';
    },
  },

  watch: {
    value: function (newValue) {
      this.selectedOption = newValue;
    }
  },

  mounted() {
    this.selectedOption = this.value;
  },

};
</script>
