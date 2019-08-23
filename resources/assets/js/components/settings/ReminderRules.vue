<style scoped>
</style>

<template>
  <div class="reminder-rules">
    <notifications group="main" position="bottom right" />

    <h3 class="mb3">
      {{ $t('settings.personalization_reminder_rule_title') }}
    </h3>
    <p>
      {{ $t('settings.personalization_reminder_rule_desc') }}
    </p>

    <div class="dt dt--fixed w-100 collapse br--top br--bottom">
      <div class="dt-row">
        <div class="dtc">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_name') }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div v-for="reminderRule in reminderRules" :key="reminderRule.id" class="dt-row bb b--light-gray">
        <div class="dtc">
          <div class="pa2">
            {{ $tc('settings.personalization_reminder_rule_line', reminderRule.number_of_days_before, {count: reminderRule.number_of_days_before}) }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2">
            <toggle-button :class="'reminder-rule-' + reminderRule.number_of_days_before" :value="reminderRule.active" :sync="true" :labels="true" @change="toggle(reminderRule)" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ToggleButton } from 'vue-js-toggle-button';

export default {

  components: {
    ToggleButton
  },

  data() {
    return {
      reminderRules: [],
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.getReminderRules();
    },

    getReminderRules() {
      axios.get('settings/personalization/reminderrules')
        .then(response => {
          this.reminderRules = response.data;
        });
    },

    toggle(reminderRule) {
      axios.post('settings/personalization/reminderrules/' + reminderRule.id)
        .then(response => {
          this.$notify({
            group: 'main',
            title: response.data,
            text: '',
            type: 'success'
          });
        });
    }
  }
};
</script>
