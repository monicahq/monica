<style scoped>
</style>

<template>
  <div>
    <datepicker :value="selectedDate"
                :name="id"
                :format="format"
                v-bind:language="locale"
                :input-class="'br2 f5 ba b--black-40 pa2 outline-0'">
    </datepicker>
  </div>
</template>

<script>
    import Datepicker from 'vuejs-datepicker'

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                format: 'yyyy-MM-dd',
                selectedDate: ''
            };
        },

        components: {
            Datepicker
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.selectedDate = new Date()  // this creates a date object in the user's timezone
            this.selectedDate.setYear(this.defaultDate.slice(0, 4))
            this.selectedDate.setMonth(parseInt(this.defaultDate.slice(5, 7)) - 1)  // months a indexed at 0 in js
            this.selectedDate.setDate(this.defaultDate.slice(8, 10))
        },

        props: {
            value: null,
            id: {
                type: String,
            },
            defaultDate: {
                type: String,
            },
            locale: {
                type: String,
            },
        },

        methods: {
        }
    }
</script>
