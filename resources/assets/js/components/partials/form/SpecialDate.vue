<style scoped>

</style>

<template>
  <div>
    <div class="flex mb3">
      <div class="mr2">
        <input type="radio" id="" v-model="selectedOption" name="birthdate" selected value="unknown">
      </div>
      <div class="pointer" @click="selectedOption = 'unknown'">
        {{ $t('people.information_edit_unknown') }}
      </div>
    </div>
    <div class="flex mb3">
      <div class="mr2">
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
      <div class="mr2">
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
              v-bind:title="''" class="mr3">
            </form-select>
            <form-select
              v-model="selectedDay"
              :options="days"
              v-bind:id="'day'"
              v-bind:title="''">
            </form-select>
          </div>
        </div>
      </div>
    </div>
    <div class="flex">
      <div class="mr2">
        <input type="radio" id="" v-model="selectedOption" name="birthdate" value="exact">
      </div>
      <div class="pointer" @click="selectedOption = 'exact'">
        {{ $t('people.information_edit_exact') }}
        <div v-if="selectedOption == 'exact'" class="mt3">

          <form-date
            v-bind:id="'birthdayDate'"
            v-bind:default-date="defaultDate"
            v-bind:locale="locale">
          </form-date>

          <div class="mt3 form-information-message br2">
            <div class="pa3 flex">
              <div class="mr3">
                <svg viewBox="0 0 20 20"><g fill-rule="evenodd"><circle cx="10" cy="10" r="9" fill="currentColor"></circle><path d="M10 0C4.486 0 0 4.486 0 10s4.486 10 10 10 10-4.486 10-10S15.514 0 10 0m0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8m1-5v-3a1 1 0 0 0-1-1H9a1 1 0 1 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2m-1-5.9a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2"></path></g></svg>
              </div>
              <div class="">
                {{ $t('people.information_edit_help') }}
              </div>
            </div>
          </div>
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
            };
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
             this.selectedOption = this.value
             this.selectedMonth = this.month
             this.selectedDay = this.day
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
        },
    }
</script>
