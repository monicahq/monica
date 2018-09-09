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
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div class="dt-row bb b--light-gray" v-for="reminderRule in reminderRules" :key="reminderRule.id">
        <div class="dtc">
          <div class="pa2">
            {{ $tc('settings.personalization_reminder_rule_line', reminderRule.number_of_days_before, {count: reminderRule.number_of_days_before}) }}
          </div>
        </div>
        <div class="dtc" v-bind:class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2">
            <toggle-button :class="'reminder-rule-' + reminderRule.number_of_days_before" :value="reminderRule.active" :sync="true" :labels="true" v-on:change="toggle(reminderRule)" />
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
    import { SweetModal, SweetModalTab } from 'sweet-modal-vue';

    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                reminderRules: [],
                dirltr: true,
            };
        },

        components: {
            SweetModal,
            SweetModalTab
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
                this.dirltr = this.$root.htmldir == 'ltr';
                this.getReminderRules();
            },

            getReminderRules() {
                axios.get('/settings/personalization/reminderrules')
                        .then(response => {
                            this.reminderRules = response.data;
                        });
            },

            toggle(reminderRule) {
                axios.post('/settings/personalization/reminderrules/' + reminderRule.id)
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
    }
</script>
