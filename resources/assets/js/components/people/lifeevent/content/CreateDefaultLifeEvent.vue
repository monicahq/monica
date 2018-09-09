<style scoped>
</style>

<template>
    <div>
        <div class="ph4 pb4 mb3 mb0-ns bb b--gray-monica">
            <form-input
              value=""
              v-bind:input-type="'text'"
              v-model="defaultEvent.name"
              v-bind:required="false">
            </form-input>
        </div>

        <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
            <label for="another" class="mr2">Date it happened</label>
            <div class="flex">
                <div class="mr3">
                    <form-select
                        v-model="selectedYear"
                        :options="years"
                        :id="'year'"
                        :title="''" :class="[ dirltr ? 'mr3' : '' ]">
                    </form-select>
                </div>
                <div class="mr3">
                    <form-select
                        v-model="selectedMonth"
                        :options="months"
                        :id="'month'"
                        :title="''" :class="[ dirltr ? 'mr3' : '' ]">
                    </form-select>
                </div>
                <div>
                    <form-select
                        v-model="selectedDay"
                        :options="days"
                        :id="'day'"
                        :title="''" :class="[ dirltr ? '' : 'mr3' ]">
                    </form-select>
                </div>
            </div>
        </div>

        <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
            <label for="another" class="mr2">Story (optional)</label>
            <form-textarea
              v-bind:required="true"
              v-bind:noLabel="true"
              v-bind:rows="4"
              v-bind:placeholder="'Placeholder'"
              v-on:contentChange="broadcastContentChange($event)">
            </form-textarea>
        </div>
    </div>
</template>

<script>
    import moment from 'moment';

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                selectedMonth: 0,
                selectedDay: 0,
                selectedYear: 0,
                defaultEvent: {
                    name: '',
                    note: '',
                    happened_at: '',
                    specific_information: '',
                },
                dirltr: true,
            };
        },

        props: {
            days: {
                type: Array,
            },
            months: {
                type: Array,
            },
            years: {
                type: Array,
            },
            day: {
                type: Number,
            },
            month: {
                type: Number,
            },
            year: {
                type: Number,
            },
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent()
            this.dirltr = this.$root.htmldir == 'ltr';
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.defaultEvent.happened_at = moment().format('YYYY-MM-DD')
            },

            broadcastContentChange(note) {
                this.defaultEvent.note = note
            },

            updateDate(date) {
                this.defaultEvent.happened_at = date
            },
        }
    }
</script>
