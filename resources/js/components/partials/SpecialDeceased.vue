<style scoped>

</style>

<template>
  <div class="pa4-ns ph3 pv2 bb b--gray-monica">
    <div class="mb3 mb0-ns">
      <form-checkbox
        v-model.lazy="deceased"
        :name="'is_deceased'"
        :value="true"
        :dclass="'flex mb2'"
      >
        <template slot="label">
          {{ $t('people.deceased_mark_person_deceased') }}
        </template>
      </form-checkbox>
      <div v-show="deceased" :class="[ dirltr ? 'ml4' : 'mr4' ]">
        <form-checkbox
          v-model.lazy="dateKnown"
          :name="'is_deceased_date_known'"
          :value="true"
          :dclass="'flex mb1'"
          @change="_focusDate()"
        >
          <template slot="label">
            {{ $t('people.deceased_know_date') }}
          </template>
        </form-checkbox>
        <div v-show="dateKnown" :class="[ dirltr ? 'ml4' : 'mr4' ]">
          <form-date
            :id="'deceased_date'"
            ref="deaceasedday"
            v-model="selectedDate"
            :label="$t('people.deceased_date_label')"
            :show-calendar-on-focus="true"
            :locale="locale"
            :validator="$v.selectedDate"
          />
          <div v-show="selectedDate != ''" class="mt2">
            <form-checkbox
              :name="'add_reminder_deceased'"
              :value="true"
              :model="reminder"
            >
              {{ $t('people.deceased_add_reminder') }}
            </form-checkbox>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import moment from 'moment';
import { validationMixin } from 'vuelidate';
import { required, numeric, helpers } from 'vuelidate/lib/validators';

const before = (param) =>
  helpers.withParams(
    { type: 'before', date: param },
    (value) => !helpers.req(value) || moment(value).isBefore(param)
  );

export default {

  mixins: [validationMixin],

  props: {
    value: {
      type: Boolean,
      default: false,
    },
    date: {
      type: String,
      default: '',
    },
    reminder: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      deceased: false,
      dateKnown: false,
      selectedDate: null,
    };
  },

  validations: {
    selectedDate: {
      required,
      before: before(moment())
    }
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },
    locale() {
      return this.$root.locale;
    }
  },

  watch: {
    value(val) {
      this.deceased = val;
    },

    date(val) {
      this.selectedDate = val;
    },
  },

  mounted() {
    this.deceased = this.value;
    this.dateKnown = this.date != '';
    this.selectedDate = this.date;
  },

  methods: {
    _focusDate() {
      setTimeout(() => {
        this.$refs.deaceasedday.focus();
      }, 100);
    },
  }
};
</script>
