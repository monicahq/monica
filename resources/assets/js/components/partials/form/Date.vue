<template>
  <div>
    <datepicker
      ref="select"
      :refName="'select'"
      :value="selectedDate"
      :format="displayValue"
      :parse-typed-date="formatTypedValue"
      :language="locale"
      :monday-first="mondayFirst"
      :input-class="'br2 f5 ba b--black-40 pa2 outline-0'"
      :typeable="true"
      :clearButton="true"
      :showCalendarOnFocus="showCalendarOnFocus"
      @input="$emit('input', exchangeValue($event))"
      @selected="update"
      @clearDate="update('')"
    />
    <input :name="id" type="hidden" :value="value" />
  </div>
</template>

<script>
import Datepicker from '@hokify/vuejs-datepicker';
import moment from 'moment';

export default {

  components: {
    Datepicker
  },

  props: {
    id: {
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
    }
  },

  data() {
    return {
      /**
       * Value of the date in exchange format
       */
      value: '',

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
  },

  mounted() {
    if (this.defaultDate !== '') {
      var mdate = moment(this.defaultDate, this.exchangeFormat);
      if (! mdate.isValid()) {
        mdate = moment();
      }
      this.selectedDate = mdate.toDate();
    }
    this.mondayFirst = moment.localeData().firstDayOfWeek() == 1;
    this.update(this.selectedDate);
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
        this.value = '';
      } else {
        var mdate = moment(date);
        if (! mdate.isValid()) {
          mdate = moment();
        }
        this.value = mdate.format(this.exchangeFormat);
      }
      this.$emit('input', this.value);
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
    }

  }
};
</script>
