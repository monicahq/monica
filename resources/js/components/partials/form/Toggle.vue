<style scoped>
</style>

<template>
  <div>
    <label
      v-if="title"
      :for="realId"
      class="mb2"
      :class="{ b: required }"
    >
      {{ title }}
    </label>
    <toggle-button
      :id="realId"
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
import { ToggleButton } from 'vue-js-toggle-button';

export default {

  components: {
    ToggleButton
  },

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
    realId() {
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
