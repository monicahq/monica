<style scoped>
</style>

<template>
  <div :class="{ 'form-group-error': validator && validator.$error }">
    <datepicker
      ref="select"
      :ref-name="'select'"
      :value="selectedDate"
      :format="displayValue"
      :parse-typed-date="formatTypedValue"
      :language="locale"
      :monday-first="mondayFirst"
      :input-class="inputClass"
      :typeable="true"
      :clear-button="true"
      :show-calendar-on-focus="showCalendarOnFocus"
      @input="onInput($event)"
      @selected="onSelected($event)"
      @clearDate="onSelected('')"
    />
    <input :name="id" type="hidden" :value="exchange" />
    <small v-if="validator && (validator.$error && validator.required !== undefined && !validator.required)" class="error">
      {{ requiredMessage }}
    </small>
    <small v-if="validator && (validator.$error && validator.before !== undefined && !validator.before)" class="error">
      {{ beforeMessage }}
    </small>
  </div>
</template>

<script>
import Datepicker from '@hokify/vuejs-datepicker';
import moment from 'moment';

export default {

  components: {
    Datepicker
  },

  model: {
    prop: 'value',
    event: 'input'
  },

  props: {
    id: {
      type: String,
      default: '',
    },
    value: {
      type: String,
      default: '',
    },
    label: {
      type: String,
      default: '',
    },
    defaultDate: {
      type: String,
      default: '',
    },
    locale: {
      type: String,
      default: '',
    },
    showCalendarOnFocus: {
      type: Boolean,
      default: false,
    },
    validator: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      /**
       * Value of the date in exchange format
       */
      exchange: '',

      selectedDate: '',
      mondayFirst: false
    };
  },

  computed: {
    /**
     * Exchange format with controller (moment format type).
     */
    exchangeFormat() {
      return 'YYYY-MM-DD';
    },

    /**
     * Display format (moment format type).
     */
    displayFormat() {
      return 'L';
    },

    inputClass() {
      var c = ['br2 f5 ba b--black-40 pa2 outline-0'];
      if (this.validator) {
        c.push({ 'error': this.validator.$error });
      }
      return c;
    },

    requiredMessage() {
      return this.$t('validation.vue.required', { field: this.label });
    },

    beforeMessage() {
      return this.$t('validation.vue.max.numeric', {
        field: this.label,
        max: this.displayValue(this.validator.$params.before.date)
      });
    },
  },

  watch: {
    value: function (newValue) {
      this.updateExchange(newValue);
    }
  },

  mounted() {
    this.updateExchange(this.value === '' ? this.defaultDate : this.value);
    this.mondayFirst = moment.localeData().firstDayOfWeek() == 1;
  },

  methods: {
    /**
     * Format date for display it.
     * @param date string in locale format
     * @return string date in display format
     */
    displayValue(date) {
      return date !== '' && date !== null ? moment(date).format(this.displayFormat) : '';
    },

    /**
     * Format date for save it.
     * @param date string in locale format
     * @return string date in exchange format
     */
    exchangeValue(date) {
      return date !== '' && date !== null ? moment(date).format(this.exchangeFormat) : '';
    },

    /**
     * Update the value of hidden input.
     * Store it in exchange format value.
     */
    update(date) {
      if (date === '' || date === null) {
        this.exchange = '';
      } else {
        var mdate = moment(date);
        if (! mdate.isValid()) {
          mdate = moment();
        }
        this.exchange = mdate.format(this.exchangeFormat);
      }
    },

    updateExchange(date) {
      this.exchange = date;
      if (this.exchange !== '') {
        var mdate = moment(this.exchange, this.exchangeFormat);
        if (! mdate.isValid()) {
          mdate = moment();
        }
        this.selectedDate = mdate.toDate();
      }
      this.update(this.selectedDate);
    },

    /**
     * Format the typed value with the locale specification.
     * @param date string in locale format
     * @return date value
     */
    formatTypedValue(date) {
      return date !== '' && date !== null ? moment(date, this.displayFormat).toDate() : '';
    },

    focus() {
      this.$refs.select.$children[0].$refs.select.focus();
    },

    onInput(event) {
      if (this.validator) {
        this.validator.$touch();
      }
      this.$emit('input', this.exchangeValue(event));
    },

    onSelected(event) {
      this.update(event);
      this.onInput(event);
    }
  }
};
</script>
