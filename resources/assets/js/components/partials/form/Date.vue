<style scoped>
</style>

<template>
  <div>
    <datepicker :value="selectedDate"
                :format="displayValue"
                :format-typed-date="formatTypedValue"
                :language="language"
                :monday-first="mondayFirst"
                :input-class="'br2 f5 ba b--black-40 pa2 outline-0'"
                :typeable="true"
                @input="$emit('input', exchangeValue($event))"
                @selected="update"
    />
    <input :name="id" type="hidden" :value="value" />
  </div>
</template>

<script>
import Datepicker from 'vuejs-datepicker-tmp';
import * as Languages from 'vuejs-datepicker-tmp/dist/locale';
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
  },

  data() {
    return {
      /**
       * Value of the date in exchange format
       */
      value: '',

      selectedDate: '',
      language: Languages.en,
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
    this.language = Languages[this.locale];
    this.selectedDate = moment(this.defaultDate, this.exchangeFormat).toDate();
    this.mondayFirst = moment.localeData().firstDayOfWeek() == 1;
    this.update(this.selectedDate);
  },

  methods: {
    displayValue(date) {
      return moment(date).format(this.displayFormat);
    },

    exchangeValue(date) {
      return moment(date).format(this.exchangeFormat);
    },

    /**
     * Update the value of hidden input.
     * Store it in exchange format value.
     */
    update(date) {
      var mdate = moment(date);
      if (! mdate.isValid()) {
        mdate = moment();
      }
      this.value = mdate.format(this.exchangeFormat);
    },

    /**
     * Format the typed value with the locale specicifcation.
     * Return in exchange format value.
     */
    formatTypedValue(date) {
      return moment(date, this.displayFormat).format(this.exchangeFormat);
    },

  }
};
</script>
