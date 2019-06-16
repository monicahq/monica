<style scoped>

</style>

<template>
  <div>
    <div class="pa4-ns ph3 pv2 bb b--gray-monica">
      <div class="mb3 mb0-ns">
        <form-radio
          :name="'birthdate'"
          :value="'unknown'"
          v-model.lazy="selectedOption"
          :dclass="'flex mb3'"
          :iclass="[ dirltr ? 'mr2' : 'ml2' ]"
        >
          <template slot="label">
            {{ $t('people.information_edit_unknown') }}
          </template>
        </form-radio>
        <form-radio
          :name="'birthdate'"
          :value="'approximate'"
          v-model.lazy="selectedOption"
          @change="event => { _focusAge() }"
          :dclass="'flex mb3'"
          :iclass="[ dirltr ? 'mr2' : 'ml2' ]"
        >
          <template slot="label">
            {{ $t('people.information_edit_probably') }}
          </template>
          <div slot="extra" v-if="selectedOption == 'approximate'">
            <form-input
              ref="age"
              :id="'age'"
              :value="age"
              :input-type="'number'"
              :width="50"
              :required="true"
            >
            </form-input>
          </div>
        </form-radio>
        <form-radio
          :name="'birthdate'"
          :value="'almost'"
          v-model.lazy="selectedOption"
          @change="event => { _focusMonth() }"
          :dclass="'flex mb3'"
          :iclass="[ dirltr ? 'mr2' : 'ml2' ]"
        >
          <template slot="label">
            {{ $t('people.information_edit_not_year') }}
          </template>
          <div slot="extra" v-if="selectedOption == 'almost'" class="mt2">
            <div class="flex">
              <form-select
                ref="month"
                :id="'month'"
                v-model="selectedMonth"
                :options="months"
                :title="''"
                :class="[ dirltr ? 'mr3' : '' ]"
              />
              <form-select
                :id="'day'"
                v-model="selectedDay"
                :options="days"
                :title="''"
                :class="[ dirltr ? '' : 'mr3' ]"
              />
            </div>
          </div>
        </form-radio>
        <form-radio
          :name="'birthdate'"
          :value="'exact'"
          v-model.lazy="selectedOption"
          @change="event => { _focusBirthday() }"
          :dclass="'flex mb3'"
          :iclass="[ dirltr ? 'mr2' : 'ml2' ]"
        >
          <template slot="label">
            {{ $t('people.information_edit_exact') }}
          </template>
          <div slot="extra" v-if="selectedOption == 'exact'" class="mt2">
            <form-date
              ref="birthday"
              :id="'birthdayDate'"
              :value="birthdate"
              :showCalendarOnFocus="true"
              :locale="locale"
              :class="[ dirltr ? 'fl' : 'fr' ]"
            />
          </div>
        </form-radio>
      </div>
    </div>

    <div v-if="selectedOption == 'exact' || selectedOption == 'almost'" class="pa4-ns ph3 pv2 bb b--gray-monica">
      <div class="mb2 mb0-ns">
          <form-checkbox
            :name="'addReminder'"
            :value="'addReminder'"
            v-model.lazy="hasBirthdayReminder"
            :dclass="[ dirltr ? 'mr2' : 'ml2' ]"
          >
            {{ $t('people.people_add_reminder_for_birthday') }}
          </form-checkbox>
      </div>
    </div>
  </div>
</template>

<script>
export default {

  props: {
    value: {
      type: String,
      default: '',
    },
    days: {
      type: Array,
      default: function () {
        return [];
      }
    },
    months: {
      type: Array,
      default: function () {
        return [];
      }
    },
    day: {
      type: Number,
      default: 0,
    },
    month: {
      type: Number,
      default: 0,
    },
    birthdate: {
      type: String,
      default: '',
    },
    age: {
      type: Number,
      default: 0,
    },
    reminder: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      selectedDate: null,
      selectedOption: null,
      selectedMonth: 0,
      selectedDay: 0,
      hasBirthdayReminder: false
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },
    locale() {
      return this.$root.locale;
    }
  },

  mounted() {
    this.selectedOption = this.value != '' ? this.value : 'unknown';
    this.selectedMonth = this.month;
    this.selectedDay = this.day;
    this.hasBirthdayReminder = this.reminder;
  },

  methods: {
    _focusAge() {
      setTimeout(() => {
        this.$refs.age.focus();
      }, 100);
    },
    _focusMonth() {
      setTimeout(() => {
        this.$refs.month.focus();
      }, 100);
    },
    _focusBirthday() {
      setTimeout(() => {
        this.$refs.birthday.focus();
      }, 100);
    },
  }
};
</script>
