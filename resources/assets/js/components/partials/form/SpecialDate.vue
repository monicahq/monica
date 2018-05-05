<style scoped>

</style>

<template>
  <div>
    <div class="pa4-ns ph3 pv2 bb b--gray-monica">
      <div class="mb3 mb0-ns">
        <div class="flex mb3">
          <div v-bind:class="[dirltr ? 'mr2' : 'ml2']">
            <input type="radio" id="" v-model="selectedOption" name="birthdate" selected value="unknown">
          </div>
          <div class="pointer" @click="selectedOption = 'unknown'">
            {{ $t('people.information_edit_unknown') }}
          </div>
        </div>
        <div class="flex mb3">
          <div v-bind:class="[dirltr ? 'mr2' : 'ml2']">
            <input type="radio" id="" v-model="selectedOption" name="birthdate" value="approximate">
          </div>
          <div class="pointer" @click="selectedOption = 'approximate'">
            {{ $t('people.information_edit_probably') }}
            <div v-if="selectedOption == 'approximate'">
              <form-input
                :value="age"
                v-bind:input-type="'number'"
                v-bind:id="'age'"
                v-bind:width="50"
                v-bind:required="true">
              ></form-input>
            </div>
          </div>
        </div>
        <div class="flex mb3">
          <div v-bind:class="[dirltr ? 'mr2' : 'ml2']">
            <input type="radio" id="" v-model="selectedOption" name="birthdate" value="almost">
          </div>
          <div class="pointer" @click="selectedOption = 'almost'">
            {{ $t('people.information_edit_not_year') }}
            <div v-if="selectedOption == 'almost'" class="mt3">
              <div class="flex">
                <form-select
                  v-model="selectedMonth"
                  :options="months"
                  v-bind:id="'month'"
                  v-bind:title="''" v-bind:class="[ dirltr ? 'mr3' : '' ]">
                </form-select>
                <form-select
                  v-model="selectedDay"
                  :options="days"
                  v-bind:id="'day'"
                  v-bind:title="''" v-bind:class="[ dirltr ? '' : 'mr3' ]">
                </form-select>
              </div>
            </div>
          </div>
        </div>
        <div class="flex">
          <div v-bind:class="[dirltr ? 'mr2' : 'ml2']">
            <input type="radio" id="" v-model="selectedOption" name="birthdate" value="exact">
          </div>
          <div class="pointer" @click="selectedOption = 'exact'">
            {{ $t('people.information_edit_exact') }}
            <div v-if="selectedOption == 'exact'" class="mt3" v-bind:class="[ dirltr ? '' : 'fr' ]">

              <form-date
                v-bind:id="'birthdayDate'"
                v-bind:default-date="defaultDate"
                v-bind:locale="locale" v-bind:class="[ dirltr ? '' : 'fr' ]">
              </form-date>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="pa4-ns ph3 pv2 bb b--gray-monica" v-if="selectedOption == 'exact' || selectedOption == 'almost'">
      <div class="mb2 mb0-ns">
        <div class="form-check">
          <label v-bind:class="[dirltr ? 'mr2 form-check-label pointer' : 'ml2 form-check-label pointer']">
            <input class="form-check-input" id="addReminder" name="addReminder" type="checkbox" value="addReminder" :checked="hasBirthdayReminder">
            {{ $t('people.people_add_reminder_for_birthday') }}
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                selectedDate: null,
                selectedOption: 'unknown',
                selectedMonth: 0,
                selectedDay: 0,
                dirltr: true,
                hasBirthdayReminder: 0
            };
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
             this.dirltr = $('html').attr('dir') == 'ltr';
             this.selectedOption = this.value
             this.selectedMonth = this.month
             this.selectedDay = this.day
             this.hasBirthdayReminder = this.reminder
        },

        props: {
            value: {
                type: String,
            },
            days: {
                type: Array,
            },
            months: {
                type: Array,
            },
            day: {
                type: Number,
            },
            month: {
                type: Number,
            },
            defaultDate: {
                type: String,
            },
            age: {
                type: String,
            },
            locale: {
                type: String,
            },
            reminder: {
                type: Number,
            },
        },
    }
</script>
