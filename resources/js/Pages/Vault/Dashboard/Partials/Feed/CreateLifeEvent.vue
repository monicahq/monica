<style lang="scss" scoped>
.type-list-item {
  &:last-child {
    border-bottom: 0;
  }
}

.close-button {
  top: -7px;
  right: -10px;
}
</style>

<template>
  <section class="relative">
    <!-- close button -->
    <svg
      xmlns="http://www.w3.org/2000/svg"
      class="close-button absolute h-4 w-4 cursor-pointer"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
      @click="$emit('cancelled')">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>

    <h1 class="mb-3 text-center text-xl">
      {{ $t('people.life_event_list_cta') }}
    </h1>

    <div v-if="!loading" class="mt4 mw7 center mb3">
      <!-- Breadcrumb -->
      <ul v-if="view == 'types' || view == 'add'" class="mb-2 rounded-md border border-gray-200 p-2 text-sm">
        <li class="inline">
          <a class="cursor-pointer" href="" @click.prevent="view = 'categories'">
            {{ $t('people.life_event_create_category') }}
          </a>
        </li>
        <li v-if="view == 'types'" class="inline">
          > {{ $t('people.life_event_category_' + activeCategory.default_life_event_category_key) }}
        </li>
        <template v-else-if="view == 'add'">
          <li class="inline">
            &gt;
            <a class="cursor-pointer" href="" @click.prevent="view = 'types'">
              {{ $t('people.life_event_category_' + activeCategory.default_life_event_category_key) }}
            </a>
          </li>
          <li class="inline">&gt; {{ $t('people.life_event_create_life_event') }}</li>
        </template>
      </ul>

      <!-- List of event categories -->
      <div v-if="view == 'categories'" class="rounded-sm border border-gray-200 bg-white">
        <ul>
          <li
            v-for="category in categories"
            :key="category.id"
            class="type-list-item relative flex cursor-pointer items-center justify-between border-b border-gray-200 p-2 hover:bg-yellow-50"
            @click="getType(category)">
            <div>
              <img
                class="mr-2 inline"
                :src="'img/people/life-events/categories/' + category.default_life_event_category_key + '.svg'"
                :alt="category.default_life_event_category_key" />
              <span>{{ $t('people.life_event_category_' + category.default_life_event_category_key) }}</span>
            </div>

            <svg
              class="life-event-add-arrow"
              width="10"
              height="13"
              viewBox="0 0 10 13"
              fill="none"
              xmlns="http://www.w3.org/2000/svg">
              <path
                d="M8.75071 5.66783C9.34483 6.06361 9.34483 6.93653 8.75072 7.33231L1.80442 11.9598C1.13984 12.4025 0.25 11.9261 0.25 11.1275L0.25 1.87263C0.25 1.07409 1.13984 0.59767 1.80442 1.04039L8.75071 5.66783Z"
                fill="#C4C4C4" />
            </svg>
          </li>
        </ul>
        <div v-if="loadingCategories" class="p-20 text-center">
          <loading />
        </div>
      </div>

      <ul v-if="view == 'types'" class="rounded-sm border border-gray-200 bg-white">
        <!-- TYPES -->
        <li
          v-for="type in types"
          :key="type.id"
          class="type-list-item relative flex cursor-pointer items-center justify-between border-b border-gray-200 p-2 hover:bg-yellow-50"
          @click="displayAddScreen(type)">
          <div>
            <img
              class="mr-2 inline"
              :src="'img/people/life-events/types/' + type.default_life_event_type_key + '.svg'"
              :alt="type.default_life_event_type_key"
              style="min-width: 12px" />
            <span v-if="type.name">
              {{ type.name }}
            </span>
            <span v-else>
              {{ $t('people.life_event_sentence_' + type.default_life_event_type_key) }}
            </span>
          </div>

          <svg
            class="life-event-add-arrow"
            width="10"
            height="13"
            viewBox="0 0 10 13"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
              d="M8.75071 5.66783C9.34483 6.06361 9.34483 6.93653 8.75072 7.33231L1.80442 11.9598C1.13984 12.4025 0.25 11.9261 0.25 11.1275L0.25 1.87263C0.25 1.07409 1.13984 0.59767 1.80442 1.04039L8.75071 5.66783Z"
              fill="#C4C4C4" />
          </svg>
        </li>
        <div v-if="loadingTypes" class="p-20 text-center">
          <loading />
        </div>
      </ul>

      <!-- ADD SCREEN -->
      <div v-if="add" class="br2 pt4 border border-gray-200">
        <div class="life-event-add-icon tc center">
          <img
            :src="'img/people/life-events/types/' + activeType.default_life_event_type_key + '.svg'"
            :alt="activeType.default_life_event_type_key"
            style="min-width: 17px" />
        </div>

        <h3 class="pt3 ph4 f3 fw5 tc">
          <template v-if="activeType.name">
            {{ activeType.name }}
          </template>
          <template v-else>
            {{ $t('people.life_event_sentence_' + activeType.default_life_event_type_key) }}
          </template>
        </h3>

        <!-- This field will be the same for every life event type no matter what, as the date is the only required field -->
        <div class="ph4 pv3 mb3 mb0-ns bb border-gray-200">
          <label for="year" class="mr-2">
            {{ $t('people.life_event_date_it_happened') }}
          </label>
          <div class="mb3 flex">
            <div class="mr-2">
              <form-select
                :id="'year'"
                v-model="selectedYear"
                :options="years"
                :title="''"
                :class="[dirltr ? 'mr-2' : '']"
                @input="updateDate" />
            </div>
            <div class="mr-2">
              <form-select
                :id="'month'"
                v-model="selectedMonth"
                :options="months"
                :title="''"
                :class="[dirltr ? 'mr-2' : '']"
                @input="updateDate" />
            </div>
            <div>
              <form-select
                :id="'day'"
                v-model="selectedDay"
                :options="days"
                :title="''"
                :class="[dirltr ? '' : 'mr-2']"
                @input="updateDate" />
            </div>
          </div>
          <p class="f6">
            {{ $t('people.life_event_create_date') }}
          </p>
        </div>

        <create-default-life-event @contentChange="updateLifeEventContent($event)" />

        <!-- YEARLY REMINDER -->
        <div class="ph4 pv3 mb3 mb0-ns bb border-gray-200">
          <form-checkbox
            v-model.lazy="newLifeEvent.has_reminder"
            :name="'addReminder'"
            :dclass="[dirltr ? 'mr3' : 'ml3']">
            {{ $t('people.life_event_create_add_yearly_reminder') }}
          </form-checkbox>
        </div>

        <!-- FORM ACTIONS -->
        <div class="ph4-ns ph3 pv3 bb border-gray-200">
          <div class="flex-ns justify-between">
            <div>
              <a
                class="btn btn-secondary tc w-auto-ns w-100 pb0-ns mb-2"
                href=""
                @click.prevent="$emit('dismissModal')">
                {{ $t('app.cancel') }}
              </a>
            </div>
            <div>
              <button class="btn btn-primary w-auto-ns w-100 pb0-ns mb-2" @click="store()">
                {{ $t('app.add') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- loading indicator when grabbing all the categories -->
    <div v-if="loading" class="p-20 text-center">
      <loading />
    </div>
  </section>
</template>

<script>
import Loading from '@/Shared/Loading';

export default {
  components: {
    Loading,
  },

  props: {
    hash: {
      type: String,
      default: '',
    },
    years: {
      type: Array,
      default: function () {
        return [];
      },
    },
    months: {
      type: Array,
      default: function () {
        return [];
      },
    },
    days: {
      type: Array,
      default: function () {
        return [];
      },
    },
  },
  emits: ['cancelled'],

  data() {
    return {
      loadingCategories: false,
      loadingTypes: false,
      selectedDay: 0,
      selectedMonth: 0,
      selectedYear: 0,
      newLifeEvent: {
        name: '',
        note: '',
        happened_at: '',
        life_event_type_id: 0,
        happened_at_month_unknown: false,
        happened_at_day_unknown: false,
        specific_information: '',
        has_reminder: false,
      },
      categories: [],
      activeCategory: '',
      activeType: '',
      types: [],
      view: 'categories',
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },
  },

  mounted() {
    this.loadingCategories = true;
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.getCategories();
    },

    displayAddScreen(type) {
      this.view = 'add';
      this.activeType = type;
      this.newLifeEvent.life_event_type_id = type.id;
    },

    getCategories() {
      axios.get('lifeevents/categories').then((response) => {
        this.categories = response.data.data;
        this.loadingCategories = false;
      });
    },

    getType(category) {
      this.loadingTypes = true;

      axios.get('lifeevents/categories/' + category.id + '/types').then((response) => {
        this.types = response.data.data;
        this.loadingTypes = false;
      });

      this.view = 'types';
      this.activeCategory = category;
    },

    updateLifeEventContent(lifeEvent) {
      this.newLifeEvent.note = lifeEvent.note;
      this.newLifeEvent.name = lifeEvent.name;
      this.newLifeEvent.specific_information = lifeEvent.specific_information;
    },

    /**
     * Sets the date when the user chooses either an empty month
     * or an empty day of the month.
     * If the user chooses an empty day, the day is set to 1 and we use
     * a boolean to indicate that the day is unknown.
     * Same for the month.
     */
    updateDate() {
      if (this.selectedDay == 0) {
        this.newLifeEvent.happened_at_day_unknown = true;
        this.newLifeEvent.happened_at = this.selectedYear + '-' + this.selectedMonth + '-01';
      } else if (this.selectedMonth == 0) {
        this.newLifeEvent.happened_at_month_unknown = true;
        this.newLifeEvent.happened_at_day_unknown = true;
        this.newLifeEvent.happened_at = this.selectedYear + '-01-01';
        this.selectedDay = 0;
      } else {
        this.newLifeEvent.happened_at = this.selectedYear + '-' + this.selectedMonth + '-' + this.selectedDay;
        this.newLifeEvent.happened_at_month_unknown = false;
        this.newLifeEvent.happened_at_day_unknown = false;
      }
    },

    store() {
      axios.post('people/' + this.hash + '/lifeevents', this.newLifeEvent).then((response) => {
        this.$emit('updateLifeEventTimeline', response.data);

        this.$notify({
          group: 'main',
          title: this.$t('people.life_event_create_success'),
          text: '',
          type: 'success',
        });
      });
    },
  },
};
</script>
