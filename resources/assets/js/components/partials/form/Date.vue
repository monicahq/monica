<style scoped>
</style>

<template>
  <div>
    <!-- <v-date-picker
        mode='single'
        v-model='selectedDate'
        id="birthdate"
        :input-read-only="true"
        :input-class="'br2 f5 w-100 ba b--black-40 pa2 outline-0'">
    </v-date-picker> -->
    <datepicker :value="defaultDate"
                :name="id"
                :format="format"
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
            };
        },

        components: {
            Datepicker
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
        },

        props: {
            value: null,
            id: {
                type: String,
            },
            defaultDate: {
                type: String,
            },
        },

        methods: {
        }
    }
</script>
