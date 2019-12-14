<style scoped>
</style>

<template>
  <div>
    <!-- LOG AN ACTIVITY -->
    <transition name="fade">
      <div v-if="displayLogActivity" class="ba br3 mb3 pa3 b--black-40">
        <div class="dt dt--fixed pb3 mb3 mb0-ns bb b--gray-monica">
          <!-- SUMMARY -->
          <div class="dtc pr2">
            <p class="mb2 b">
              {{ $t('people.activities_add_title', { name: name }) }}
            </p>
            <form-input
              :id="'last_name'"
              v-model="newActivity.summary"
              :input-type="'text'"
              :required="false"
            />
          </div>

          <!-- WHEN -->
          <div class="dtc">
            <p class="mb2 b">
              {{ $t('people.activities_add_date_occured') }}
            </p>
            <div class="di">
              <div class="dib">
                <form-date
                  v-model="newActivity.happened_at"
                  :default-date="todayDate"
                  :locale="locale"
                  @selected="updateDate($event)"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- ADDITIONAL FIELDS -->
        <div v-show="!displayDescription || !displayEmotions || !displayCategory" class="bb b--gray-monica pv3 mb3">
          <ul class="list">
            <li v-show="!displayDescription" class="di pointer mr3">
              <a @click.prevent="displayDescription = true" href="">{{ $t('people.activities_add_more_details') }}</a>
            </li>
            <li v-show="!displayEmotions" class="di pointer mr3">
              <a @click.prevent="displayEmotions = true" href="">{{ $t('people.activities_add_emotions') }}</a>
            </li>
            <li v-show="!displayCategory" class="di pointer mr3">
              <a @click.prevent="displayCategory = true" href="">{{ $t('people.activities_add_category') }}</a>
            </li>
            <li v-show="!displayParticipants" class="di pointer">
              <a @click.prevent="displayParticipants = true" href="">{{ $t('people.activities_add_participants_cta') }}</a>
            </li>
          </ul>
        </div>

        <!-- DESCRIPTION -->
        <div v-show="displayDescription" class="bb b--gray-monica pv3 mb3">
          <label>
            {{ $t('people.activities_summary') }}
          </label>
          <form-textarea
            v-model="newActivity.description"
            :required="true"
            :no-label="true"
            :rows="4"
            :placeholder="$t('people.conversation_add_content')"
            @contentChange="updateDescription($event)"
          />
          <p class="f6">
            {{ $t('app.markdown_description') }} <a href="https://guides.github.com/features/mastering-markdown/" rel="noopener noreferrer" target="_blank">
              {{ $t('app.markdown_link') }}
            </a>
          </p>
        </div>

        <!-- EMOTIONS -->
        <div v-show="displayEmotions" class="bb b--gray-monica pb3 mb3">
          <label>
            {{ $t('people.activities_add_emotions') }}
          </label>
          <emotion class="pv2" @updateEmotionsList="updateEmotionsList" />
        </div>

        <!-- ACTIVITY CATEGORIES -->
        <div v-show="displayCategory" class="bb b--gray-monica pb3 mb3">
          <label>
            {{ $t('people.activities_add_pick_activity') }}
          </label>
          <activity-type-list @change="updateCategory($event)" />
        </div>

        <!-- PARTICPANTS -->
        <div v-show="displayParticipants" class="bb b--gray-monica pb3 mb3">
          <label>
            {{ $t('people.activities_add_participants', {name: name}) }}
          </label>
          <participant-list :hash="hash" @update="updateParticipant($event)" />
        </div>

        <error :errors="errors" />

        <!-- ACTIONS -->
        <div class="pt3">
          <div class="flex-ns justify-between">
            <div class="">
              <a class="btn btn-secondary tc w-auto-ns w-100 mb2 pb0-ns" @click.prevent="resetFields()">
                {{ $t('app.cancel') }}
              </a>
            </div>
            <div class="">
              <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" @click.prevent="store()">
                {{ $t('app.add') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import moment from 'moment';
import Error from '../../partials/Error.vue';

export default {
  components: {
    Error
  },

  filters: {
    moment: function (date) {
      return moment.utc(date).format('LL');
    }
  },

  props: {
    hash: {
      type: String,
      default: '',
    },
    name: {
      type: String,
      default: '',
    },
    displayLogActivity: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      emotions: [],
      displayDescription: false,
      displayEmotions: false,
      displayCategory: false,
      displayParticipants: false,
      newActivity: {
        summary: '',
        description: '',
        happened_at: '',
        emotions: [],
        activity_type_id: null,
        participants: [],
      },
      errors: [],
    };
  },

  computed: {
    locale() {
      return this.$root.locale;
    },

    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.todayDate = moment().format('YYYY-MM-DD');
      this.newActivity.happened_at = this.todayDate;
    },

    resetFields() {
      this.displayDescription = false;
      this.displayEmotions = false;
      this.displayCategory = false;
      this.displayParticipants = false;
      this.newActivity.summary = '';
      this.newActivity.participants = [];
      this.description = '';
      this.happened_at = '';
      this.emotions = [];
      this.activity_type_id = 0;
      this.errors = [];
      this.$emit('cancel');
    },

    updateDescription(description) {
      this.newActivity.description = description;
    },

    updateDate(date) {
      this.newActivity.happened_at = date;
    },

    updateCategory(id) {
      this.newActivity.activity_type_id = parseInt(id);
    },

    store() {
      axios.post('/people/' + this.hash + '/activities', this.newActivity)
        .then(response => {
          this.resetFields();
          this.$emit('update', response.data.data);

          this.$notify({
            group: 'main',
            title: this.$t('people.activities_add_success'),
            text: '',
            type: 'success'
          });
        })
        .catch(error => {
          this.errors = _.flatten(_.toArray(error.response.data));
        });
    },

    updateParticipant: function (participants) {
      this.newActivity.participants = participants;
    },

    updateEmotionsList: function(emotions) {
      this.emotions = emotions;
      this.newActivity.emotions = [];

      // filter the list of emotions to populate a new array
      // containing only the emotion ids and not the entire objetcs
      for (let i = 0; i < this.emotions.length; i++) {
        this.newActivity.emotions.push(this.emotions[i].id);
      }
    }
  }
};
</script>
