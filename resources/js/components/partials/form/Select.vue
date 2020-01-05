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

.error {
  color:#f57f6c;
}

select.error {
  border-color: #f79483;
}

.form-group-error {
  color:#f57f6c;

  animation-name: shake;
  animation-fill-mode: forwards;
  animation-duration: .6s;
  animation-timing-function: ease-in-out;
}

@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }

  15%, 45%, 75% {
    transform: translateX(0.375rem);
  }

  30%, 60%, 90% {
    transform: translateX(-0.375rem);
  }
}

</style>

<template>
  <div :class="{ 'form-group-error': validator && validator.$error }">
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
    <small v-if="validator && (validator.$error && !validator.required)">
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
    name: {
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
        c.push({ 'error': this.validator.$error });
      }
      return c;
    },
    fieldName() {
      return this.name && this.name.length > 0 ? this.name : this.title;
    },
    requiredMessage() {
      return this.$t('validation.vue.required', { field: this.fieldName })
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
