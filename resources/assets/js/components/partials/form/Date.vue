<style scoped>
</style>

<template>
  <div>
    <datepicker :value="selectedDate"
                :format="customFormatter"
                :language="language"
                :monday-first="mondayFirst"
                :input-class="'br2 f5 ba b--black-40 pa2 outline-0'"
                @input="update"
                @selected="$emit('selected', getDateInEloquentFormat($event))"
    />
    <input :name="id" type="hidden" :value="value" />
  </div>
</template>

<script>
import Datepicker from 'vuejs-datepicker';
import * as Languages from 'vuejs-datepicker/dist/locale';
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

    mounted() {
        this.language = Languages[this.locale];
        this.selectedDate = moment(this.defaultDate, this.exchangeFormat()).toDate();
        this.mondayFirst = moment.localeData().firstDayOfWeek() == 1;
        this.update(this.selectedDate);
    },

    methods: {
        customFormatter(date) {
            return moment(date).format('L');
        },

        getDateInEloquentFormat(date) {
            return moment(date).format(this.exchangeFormat());
        },

        /**
          * Update the value of hidden input, in exchange format value
          */
        update(date) {
            var mdate = moment(date);
            if (! mdate.isValid()) {
                mdate = moment();
            }
            this.value = mdate.format(this.exchangeFormat());
        },

        /**
          * Exchange format with controller (moment format type)
          */
        exchangeFormat() {
            return 'YYYY-MM-DD';
        }
    }
};
</script>
