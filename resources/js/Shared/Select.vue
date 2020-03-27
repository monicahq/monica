<style scoped>
.select {
  height: 34px;
  transition: all;
  transition-duration: 0.2s;
  border: 1px solid #c4cdd5;
}
.select:focus {
  border: 1px solid #5c6ac4;
}
</style>

<template>
  <div>
    <label
      v-if="label"
      :for="name"
      :class="customLabelClasses"
    >
      {{ label }}
    </label>
    <select
      :id="id"
      ref="select"
      :value="selectedOption"
      :name="name"
      :required="required"
      :class="customSelectClasses"
      @input="onInput"
    >
      <template v-if="Array.isArray(options)">
        <option
          v-for="option in filterExclude(options)"
          :key="option.id"
          :value="option.id"
        >
          {{ option.name }}
        </option>
      </template>
      <template v-else>
        <optgroup
          v-for="(optgroup,index) in options"
          :key="index"
          :label="optgroup.name"
        >
          <option
            v-for="option in filterExclude(optgroup.options)"
            :key="option.id"
            :value="option.id"
          >
            {{ option.name }}
          </option>
        </optgroup>
      </template>
    </select>
  </div>
</template>

<script>
export default {

  props: {
    value: {
      type: [String, Number],
      default: '',
    },
    options: {
      type: [Array, Object],
      default: () => [],
    },
    title: {
      type: String,
      default: '',
    },
    label: {
      type: String,
      default: null,
    },
    id: {
      type: String,
      default: '',
    },
    name: {
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
    labelClass: {
      type: String,
      default: '',
    },
    selectClass: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      selectedOption: null,
    };
  },

  created: function() {
    this.customSelectClasses = this.selectClass + ' ' + this.customSelectClasses;
    this.customLabelClasses = this.labelClass + ' ' + this.customLabelClasses;

    if (this.required) {
      this.customLabelClasses = this.customLabelClasses + ' b';
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
    filterExclude: function (options) {
      var me = this;
      return options.filter(function (option) {
        return option.id != me.excludedId;
      });
    },

    focus() {
      this.$refs.select.focus();
    },

    onInput(event) {
      this.$emit('input', event.target.value);
    },
  },
};
</script>
