<style scoped>

</style>

<template>
  <div>
    <div class="pa4-ns ph3 pv2 bb b--gray-monica">
      <div class="mb3 mb0-ns">
        <form-radio
          v-model.lazy="selectedOption"
          :name="'birthdate'"
          :value="'unknown'"
          :dclass="'flex mb3'"
          :iclass="[ dirltr ? 'mr2' : 'ml2' ]"
        >
          <template slot="label">
            {{ $t('people.information_edit_unknown') }}
          </template>
        </form-radio>
        <form-radio
          v-model.lazy="selectedOption"
          :name="'birthdate'"
          :value="'approximate'"
          :dclass="'flex mb3'"
          :iclass="[ dirltr ? 'mr2' : 'ml2' ]"
          @change="event => { _focusAge() }"
        >
          <template slot="label">
            {{ $t('people.information_edit_probably') }}
          </template>
          <div v-if="selectedOption == 'approximate'" slot="extra">
            <form-input
              :id="'age'"
              ref="age"
              :value="age"
              :input-type="'number'"
              :width="50"
              :required="true"
            />
          </div>
        </form-radio>
        <form-radio
          v-model.lazy="selectedOption"
          :name="'birthdate'"
          :value="'almost'"
          :dclass="'flex mb3'"
          :iclass="[ dirltr ? 'mr2' : 'ml2' ]"
          @change="event => { _focusMonth() }"
        >
          <template slot="label">
            {{ $t('people.information_edit_not_year') }}
          </template>
          <div v-if="selectedOption == 'almost'" slot="extra" class="mt2">
            <div class="flex">
              <form-select
                :id="'month'"
                ref="month"
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
          v-model.lazy="selectedOption"
          :name="'birthdate'"
          :value="'exact'"
          :dclass="'flex mb3'"
          :iclass="[ dirltr ? 'mr2' : 'ml2' ]"
          @change="event => { _focusBirthday() }"
        >
          <template slot="label">
            {{ $t('people.information_edit_exact') }}
          </template>
          <div v-if="selectedOption == 'exact'" slot="extra" class="mt2">
            <form-date
              :id="'birthdayDate'"
              ref="birthday"
              :value="birthdate"
              :show-calendar-on-focus="true"
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
          v-model.lazy="hasBirthdayReminder"
          :name="'addReminder'"
          :value="'addReminder'"
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
