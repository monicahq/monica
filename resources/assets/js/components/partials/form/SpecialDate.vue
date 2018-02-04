<style scoped>
</style>

<template>
  <div>
    <div class="flex">
      <div class="">
        <input type="radio" id="" name="birthdate" class="" value="on">
      </div>
      <div class="">
        I do not know this person’s age
      </div>
    </div>
    <div class="flex">
      <div class="">
        <input type="radio" id="" name="birthdate" class="" value="on">
      </div>
      <div class="">
        This person is probably…
        <form-input></form-input>
      </div>
    </div>
    <div class="flex">
      <div class="">
        <input type="radio" id="" name="birthdate" class="" value="on">
      </div>
      <div class="">
        I know the exact birthdate of this person…
        <v-date-picker
            mode='single'
            v-model='selectedDate'>
          </v-date-picker>
      </div>
    </div>
    <p class="mb2" v-bind:class="{ b: required }" v-if="title">{{ title }}</p>
    <select
        v-model="selectedOption"
        @input="event => { $emit('input', event.target.value) }"
        :id="id"
        :name="id"
        required
        class="br2 f5 w-100 ba b--black-40 pa2 outline-0">
        <option v-for="option in options" :value="option.id" v-if="option.id != excludedId">{{ option.name }}</option>
    </select>
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
                selectedOption: null
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
