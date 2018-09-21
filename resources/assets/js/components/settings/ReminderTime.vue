<style scoped>
</style>

<template>
    <div>
        <!-- Timezone -->
        <div class="form-group">
            <form-select
                :value="updatedTimezone"
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
                :value="updatedReminder"
                :id="'reminder_time'"
                :options="hours"
                :title="$t('settings.reminder_time_to_send')"
                @input="reminderUpdate"
                :required="true"
                :formClass="'form-control'">
            </form-select>
            <small class="form-text text-muted" v-html="message"></small>
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
                message: '',
                updatedTimezone: '',
                updatedReminder: ''
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
                this.updatedReminder = this.reminder
                this.updatedTimezone = this.timezone
                this.computeMessage()
            },

            timezoneUpdate: function(event) {
                this.updatedTimezone = event;
                this.computeMessage();
            },

            reminderUpdate: function(event) {
                this.updatedReminder = event;
                this.computeMessage();
            },

            computeMessage() {
                var moment = require('moment-timezone');
                moment.locale(this._i18n.locale);
                moment.tz.setDefault('UTC');

                var now = moment();
                var t = now.format('YYYY-MM-DD ' + this.updatedReminder + ':00');

                var date = moment.tz(t, this.updatedTimezone);

                if (date.isBefore(now)) {
                  date = date.add(1, 'days');
                }

                this.message = this.$t('settings.reminder_time_to_send_help', {
                    dateTime: date.format('LLL'),
                    dateTimeUtc: date.utc().format('YYYY-MM-DD HH:mm z')
                });
            }
        }
    }
</script>
