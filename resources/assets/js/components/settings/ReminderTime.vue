<style scoped>
</style>

<template>
    <div>
        <!-- Timezone -->
        <div class="form-group">
            <form-select
                :value="timezone"
                :id="'timezone'"
                :options="timezones"
                :title="$t('settings.timezone')"
                @input="timezoneUpdate"
                :required="true"
                :formClass="'form-control'">
            </form-select>
        </div>

        <!-- Reminders -->
        <div class="form-group">
            <form-select
                :value="reminder"
                :id="'reminder_time'"
                :options="hours"
                :title="$t('settings.reminder_time_to_send')"
                @input="reminderUpdate"
                :required="true"
                :formClass="'form-control'">
            </form-select>
            <small class="form-text text-muted">
                {{ $t('settings.reminder_time_to_send_help', {dateTime:formatted}) }}
            </small>
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
                formatted: '',
                formattedUtc: '',
            };
        },

        props: {
            timezone: {
                type: String,
            },
            timezones: {
                type: Array,
            },
            reminder: {
                type: String,
            },
            hours: {
                type: Array,
            }
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                var moment = require('moment-timezone');
                moment.locale(this._i18n.locale);
                moment.tz.setDefault('UTC');

                var now = moment();
                var t = now.format('YYYY-MM-DD '+this.reminder+':00');

                var date = moment.tz(t, this.timezone);

                if (date.isBefore(now)) {
                  date = date.add(1, 'days');
                }

                this.formatted = date.format('lll');
            },

            timezoneUpdate: function(event) {
                this.timezone = event.target.value;
                this.prepareComponent();
            },

            reminderUpdate: function(event) {
                this.reminder = event.target.value;
                this.prepareComponent();
            },
        }
    }
</script>
