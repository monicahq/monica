<style scoped>

</style>

<template>
  <div>
    <div class="flex mb3">
      <div class="mr2">
        <input type="radio" id="" v-model="selectedOption" name="birthdate" selected value="unknown">
      </div>
      <div class="pointer" @click="selectedOption = 'unknown'">
        I do not know this person’s age
      </div>
    </div>
    <div class="flex mb3">
      <div class="mr2">
        <input type="radio" id="" v-model="selectedOption" name="birthdate" value="approximate">
      </div>
      <div class="pointer" @click="selectedOption = 'approximate'">
        This person is probably…
        <div v-if="selectedOption == 'approximate'">
          <form-input
            :value="age"
            v-bind:input-type="'number'"
            v-bind:id="'age'"
            v-bind:width="100"
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
        I know the day and month of the birthdate of this person, but not the year…
        <div v-if="selectedOption == 'almost'" class="mt3">
          <div class="flex">
            <form-select
              v-model="selectedMonth"
              :options="genders"
              v-bind:id="'month'"
              v-bind:title="''" class="mr3">
            </form-select>
            <form-select
              v-model="selectedDay"
              :options="genders"
              v-bind:id="'month'"
              v-bind:title="''">
            </form-select>
          </div>
        </div>
      </div>
    </div>
    <div class="flex mb3">
      <div class="mr2">
        <input type="radio" id="" v-model="selectedOption" name="birthdate" value="exact">
      </div>
      <div class="pointer" @click="selectedOption = 'exact'">
        I know the exact birthdate of this person…
        <div v-if="selectedOption == 'exact'" class="mt3">
          <v-date-picker
              mode='single'
              v-model='selectedDate'
              :input-class="'br2 f5 w-100 ba b--black-40 pa2 outline-0'">
          </v-date-picker>
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
                age: 0,
            };
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
             this.selectedOption = this.value
        },

        props: {
            value: null,
            options: {
                type: Array,
            },
            title: {
                type: String,
            },
            id: {
                type: String,
            },
            excludedId: {
                type: String,
            },
            required: {
              type: Boolean,
            },
        },

        watch: {
            value: function (newValue) {
                this.selectedOption = newValue
            }
        }
    }
</script>
