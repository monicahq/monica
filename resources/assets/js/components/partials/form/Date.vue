<style scoped>
</style>

<template>
  <div>
    <datepicker :value="selectedDate"
                :format="customFormatter"
                :format-typed-date="formatTypedDate"
                :language="language"
                :monday-first="mondayFirst"
                :input-class="'br2 f5 ba b--black-40 pa2 outline-0'"
                :typeable="true"
                @input="$emit('input', getDateInEloquentFormat($event))"
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
    customFormatter(date) {
      return moment(date).format(this.displayFormat);
    },

    getDateInEloquentFormat(date) {
      return moment(date).format(this.exchangeFormat);
    },

    /**
     * Update the value of hidden input, in exchange format value
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
     * Returns in exchangeFormat.
     */
    formatTypedDate(date) {
      var mdate = moment(date, this.displayFormat);
      if (! mdate.isValid()) {
        mdate = moment();
      }
      return mdate.format(this.exchangeFormat);
    },

  }
};
</script>
