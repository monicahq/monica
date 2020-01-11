<style scoped>

</style>

<template>
  <div class="pa4-ns ph3 pv2 bb b--gray-monica">
    <div class="mb3 mb0-ns">
      <form-checkbox
        v-model="value"
        :name="'is_deceased'"
        :value="true"
        :dclass="'flex mb2'"
      >
        <template slot="label">
          {{ $t('people.deceased_mark_person_deceased') }}
        </template>
      </form-checkbox>
      <div v-show="value" :class="[ dirltr ? 'ml4' : 'mr4' ]">
        <form-checkbox
          v-model.lazy="dateKnown"
          :name="'is_deceased_date_known'"
          :value="true"
          :dclass="'flex mb1'"
          @change="_focusDate()"
        >
          <template slot="label">
            {{ $t('people.deceased_know_date') }}
          </template>
        </form-checkbox>
        <div v-show="dateKnown" :class="[ dirltr ? 'ml4' : 'mr4' ]">
          <form-date
            :id="'deceased_date'"
            ref="deaceasedday"
            v-model="date"
            :show-calendar-on-focus="true"
            :locale="locale"
          />
          <div v-show="date != ''" class="mt2">
            <form-checkbox
              :name="'add_reminder_deceased'"
              :value="true"
              :model="reminder"
            >
              {{ $t('people.deceased_add_reminder') }}
            </form-checkbox>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {

  props: {
    value: {
      type: Boolean,
      default: false,
    },
    date: {
      type: String,
      default: '',
    },
    reminder: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      dateKnown: false,
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },
    locale() {
      return this.$root.locale;
    }
  },

  mounted() {
    this.dateKnown = this.date != '';
  },

  methods: {
    _focusDate() {
      setTimeout(() => {
        this.$refs.deaceasedday.focus();
      }, 100);
    },
  }
};
</script>
