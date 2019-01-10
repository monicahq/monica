<style scoped>
</style>

<template>
  <div>
    <!-- Timezone -->
    <div class="form-group">
      <form-select
        :id="'timezone'"
        :value="updatedTimezone"
        :options="timezones"
        :title="$t('settings.timezone')"
        :required="true"
        :form-class="'form-control'"
        @input="timezoneUpdate"
      />
    </div>

    <!-- Reminders -->
    <div class="form-group">
      <form-select
        :id="'reminder_time'"
        :value="updatedReminder"
        :options="hours"
        :title="$t('settings.reminder_time_to_send')"
        :required="true"
        :form-class="'form-control'"
        @input="reminderUpdate"
      />
      <small class="form-text text-muted" v-html="message"></small>
    </div>
  </div>
</template>

<script>
export default {

  props: {
    timezone: {
      type: String,
      default: 'UTC',
    },
    timezones: {
      type: Array,
      default: function () {
        return [];
      }
    },
    reminder: {
      type: String,
      default: '',
    },
    hours: {
      type: Array,
      default: function () {
        return [];
      }
    }
  },

  data() {
    return {
      message: '',
      updatedTimezone: '',
      updatedReminder: ''
    };
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.updatedReminder = this.reminder;
      this.updatedTimezone = this.timezone;
      this.computeMessage();
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
};
</script>
