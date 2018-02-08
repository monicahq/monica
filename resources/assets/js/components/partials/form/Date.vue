<style scoped>
</style>

<template>
  <div>
    <v-date-picker
        mode='single'
        v-model='selectedDate'
        id="birthdate"
        :input-read-only="true"
        :input-class="'br2 f5 w-100 ba b--black-40 pa2 outline-0'">
    </v-date-picker>
    <input type="text" :name="id" :id="id" style="display: none;" :value="selectedDate">
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                selectedDate: new Date(),
            };
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
             this.selectedDate = this.defaultDate
        },

        props: {
            value: null,
            id: {
                type: String,
            },
            defaultDate: {
                type: Date,
            },
        }
    }
</script>
