<style scoped>
</style>

<template>
  <div class="reminder-rules">
    <notifications group="main" position="bottom right" />

    <h3 class="mb3">
      {{ $t('settings.personalization_module_title') }}
    </h3>
    <p>
      {{ $t('settings.personalization_module_desc') }}
    </p>

    <div v-if="limited" class="mt3 mb3 form-information-message br2">
      <div class="pa3 flex">
        <div class="mr3">
          <svg viewBox="0 0 20 20">
            <g fill-rule="evenodd">
              <circle cx="10" cy="10" r="9" fill="currentColor" /><path d="M10 0C4.486 0 0 4.486 0 10s4.486 10 10 10 10-4.486 10-10S15.514 0 10 0m0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8m1-5v-3a1 1 0 0 0-1-1H9a1 1 0 1 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2m-1-5.9a1.1 1.1 0 1 0 0-2.2 1.1 1.1 0 0 0 0 2.2" />
            </g>
          </svg>
        </div>
        <div v-html="$t('settings.personalisation_paid_upgrade_vue', {url: 'settings/subscriptions' })"></div>
      </div>
    </div>

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

      <div v-for="module in modules" :key="module.id" class="dt-row hover bb b--light-gray">
        <div class="dtc">
          <div class="pa2">
            {{ module.name }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2">
            <form-toggle
              v-model="module.active"
              :iclass="'module-'"
              :disabled="limited"
              :labels="true"
              @change="toggle(module, $event)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {

  props: {
    limited: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      modules: [],
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
      this.getModules();
      if (!this.limited) {
        this.limited = false;
      }
    },

    getModules() {
      axios.get('settings/personalization/modules')
        .then(response => {
          this.modules = response.data;
        });
    },

    toggle(module) {
      axios.post('settings/personalization/modules/' + module.id)
        .then(response => {
          this.$notify({
            group: 'main',
            title: this.$t('settings.personalization_module_save'),
            text: '',
            type: 'success'
          });
          this.$set(module, 'active', response.data.data.active);
        });
    }
  }
};
</script>
