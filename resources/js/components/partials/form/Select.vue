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
  <div :class="{ 'form-group-error': validator && validator.$error }">
    <label
      v-if="title"
      :for="realid"
      class="mb2"
      :class="{ b: required, error: validator && validator.$error }"
    >
      {{ title }}
    </label>
    <select
      :id="realid"
      ref="select"
      :value="selectedOption"
      :name="id"
      :required="required"
      :class="selectClass"
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
    <small v-if="validator && (validator.$error && validator.required !== undefined && !validator.required)" class="error">
      {{ requiredMessage }}
    </small>
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
    validator: {
      type: Object,
      default: null,
    }
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
      var c = [this.iclass != '' ? this.iclass : 'br2 f5 w-100 ba b--black-40 pa2 outline-0'];
      if (this.validator) {
        c.push({ error: this.validator.$error });
      }
      c.push('select');
      return c;
    },
    field() {
      return this.label && this.label.length > 0 ? this.label : this.title;
    },
    requiredMessage() {
      return this.$t('validation.vue.required', { field: this.field });
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

    focus() {
      this.$refs.select.focus();
    },

    onInput(event) {
      if (this.validator) {
        this.validator.$touch();
      }
      this.$emit('input', event.target.value);
    },
  },
};
</script>
