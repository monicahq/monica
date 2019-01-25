<style scoped>

</style>

<template>
  <div>
    <div class="pa4-ns ph3 pv2 bb b--gray-monica">
      <div class="mb3 mb0-ns">
        <div class="flex mb3">
          <div :class="[dirltr ? 'mr2' : 'ml2']">
            <input id="" v-model="selectedOption" type="radio" name="birthdate" selected
                   value="unknown"
            />
          </div>
          <div class="pointer" @click="selectedOption = 'unknown'">
            {{ $t('people.information_edit_unknown') }}
          </div>
        </div>
        <div class="flex mb3">
          <div :class="[dirltr ? 'mr2' : 'ml2']">
            <input id="" v-model="selectedOption" type="radio" name="birthdate" value="approximate" />
          </div>
          <div class="pointer" @click="selectedOption = 'approximate'">
            {{ $t('people.information_edit_probably') }}
            <div v-if="selectedOption == 'approximate'">
              <form-input
                :id="'age'"
                :value="age"
                :input-type="'number'"
                :width="50"
                :required="true"
              >
                >
              </form-input>
            </div>
          </div>
        </div>
        <div class="flex mb3">
          <div :class="[dirltr ? 'mr2' : 'ml2']">
            <input id="" v-model="selectedOption" type="radio" name="birthdate" value="almost" />
          </div>
          <div class="pointer" @click="selectedOption = 'almost'">
            {{ $t('people.information_edit_not_year') }}
            <div v-if="selectedOption == 'almost'" class="mt3">
              <div class="flex">
                <form-select
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
          </div>
        </div>
        <div class="flex">
          <div :class="[dirltr ? 'mr2' : 'ml2']">
            <input id="" v-model="selectedOption" type="radio" name="birthdate" value="exact" />
          </div>
          <div class="pointer" @click="selectedOption = 'exact'">
            {{ $t('people.information_edit_exact') }}
            <div v-if="selectedOption == 'exact'" class="mt3">
              <form-date
                :id="'birthdayDate'"
                :default-date="defaultDate"
                :locale="locale"
                :class="[ dirltr ? '' : 'fr' ]"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="selectedOption == 'exact' || selectedOption == 'almost'" class="pa4-ns ph3 pv2 bb b--gray-monica">
      <div class="mb2 mb0-ns">
        <div class="form-check">
          <label :class="[dirltr ? 'mr2 form-check-label pointer' : 'ml2 form-check-label pointer']">
            <input id="addReminder" class="form-check-input" name="addReminder" type="checkbox" value="addReminder"
                   :checked="hasBirthdayReminder"
            />
            {{ $t('people.people_add_reminder_for_birthday') }}
          </label>
        </div>
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
    defaultDate: {
      type: String,
      default: '',
    },
    age: {
      type: String,
      default: '',
    },
    reminder: {
      type: Number,
      default: 0,
    },
  },

  data() {
    return {
      selectedDate: null,
      selectedOption: 'unknown',
      selectedMonth: 0,
      selectedDay: 0,
      hasBirthdayReminder: 0
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
    this.selectedOption = this.value;
    this.selectedMonth = this.month;
    this.selectedDay = this.day;
    this.hasBirthdayReminder = this.reminder;
  },
};
</script>
