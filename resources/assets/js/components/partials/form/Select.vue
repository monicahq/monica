<style scoped>
select {
  height: 34px;
  transition: all;
  transition-duration: 0.2s;
  border: 1px solid #c4cdd5;
}
select:focus {
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
    <select
      :id="realid"
      :value="selectedOption"
      :name="id"
      required
      :class="selectClass"
      @input="event => { $emit('input', event.target.value) }"
    >
      <option
        v-for="option in filterExclude(options)"
        :key="option.id"
        :value="option.id"
      >
        {{ option.name }}
      </option>
    </select>
  </div>
</template>

<script>
export default {

  props: {
    value: {
      type: String,
      default: '',
    },
    options: {
      type: Array,
      default: function () {
        return [];
      }
    },
    title: {
      type: String,
      default: '',
    },
    id: {
      type: String,
      default: '',
    },
    excludedId: {
      type: Number,
      default: -1,
    },
    required: {
      type: Boolean,
      default: true,
    },
    iclass: {
      type: String,
      default: '',
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
    selectClass() {
      return this.iclass != '' ? this.iclass : 'br2 f5 w-100 ba b--black-40 pa2 outline-0';
    }
  },

  watch: {
    value: function (newValue) {
      this.selectedOption = newValue;
    }
  },

  mounted() {
    this.selectedOption = this.value;
  },

  methods: {
    /**
     * Filter options
     */
    filterExclude: function (options) {
      var me = this;
      return options.filter(function (option) {
        return option.id != me.excludedId;
      });
    },
  },
};
</script>
