<template>
  <div>
    <!-- LOG AN ACTIVITY -->
    <transition name="fade">
      <div class="ba br3 mb3 pa3 b--black-40">
        <div class="dt dt--fixed pb3 mb3 mb0-ns bb b--gray-monica">
          <!-- SUMMARY -->
          <div class="dtc pr2">
            <form-input
              :id="'summary'"
              v-model="newActivity.summary"
              :input-type="'text'"
              :title="$t('people.activities_add_title', { name: name })"
              :required="true"
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
                  ref="date"
                  v-model="newActivity.happened_at"
                  :show-calendar-on-focus="true"
                  :default-date="todayDate"
                  :locale="locale"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- ADDITIONAL FIELDS -->
        <div v-show="!displayDescription || !displayEmotions || !displayCategory || !displayParticipants" class="bb b--gray-monica pv3 mb3">
          <ul class="list">
            <li v-show="!displayDescription" class="di pointer mr3 nowrap-link">
              <a href="" @click.prevent="displayDescription = true">{{ $t('people.activities_add_more_details') }}</a>
            </li>
            <li v-show="!displayEmotions" class="di pointer mr3 nowrap-link">
              <a href="" @click.prevent="displayEmotions = true">{{ $t('people.activities_add_emotions') }}</a>
            </li>
            <li v-show="!displayCategory" class="di pointer mr3 nowrap-link">
              <a v-cy-name="'activities_add_category'" href="" @click.prevent="displayCategory = true">{{ $t('people.activities_add_category') }}</a>
            </li>
            <li v-show="!displayParticipants" class="di pointer nowrap-link">
              <a href="" @click.prevent="displayParticipants = true">{{ $t('people.activities_add_participants_cta') }}</a>
            </li>
          </ul>
        </div>

        <!-- DESCRIPTION -->
        <div v-if="displayDescription" class="bb b--gray-monica pv3 mb3">
          <form-textarea
            v-model="newActivity.description"
            :required="true"
            :no-label="true"
            :rows="4"
            :title="$t('people.activities_summary')"
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
        <div v-if="displayEmotions" class="bb b--gray-monica pb3 mb3">
          <label>
            {{ $t('people.activities_add_emotions_title') }}
          </label>
          <emotion
            class="pv2"
            :initial-emotions="initialEmotions"
            @update="updateEmotionsList"
          />
        </div>

        <!-- ACTIVITY CATEGORIES -->
        <div v-if="displayCategory" class="bb b--gray-monica pb3 mb3">
          <activity-type-list
            v-model="newActivity.activity_type_id"
            :title="$t('people.activities_add_pick_activity')"
          />
        </div>

        <!-- PARTICPANTS -->
        <div v-if="displayParticipants" class="bb b--gray-monica pb3 mb3">
          <label>
            {{ $t('people.activities_add_participants', {name: name}) }}
          </label>
          <participant
            :hash="hash"
            :initial-participants="participants"
            @update="updateParticipant($event)"
          />
        </div>

        <error :errors="errors" />

        <!-- ACTIONS -->
        <div class="pt3">
          <div class="flex-ns justify-between">
            <div class="">
              <a class="btn btn-secondary tc w-auto-ns w-100 mb2 pb0-ns" @click.prevent="close()">
                {{ $t('app.cancel') }}
              </a>
            </div>
            <div class="">
              <button v-cy-name="'save-activity-button'" class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" @click.prevent="store()">
                {{ activity ? $t('app.save') : $t('app.add') }}
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
import ActivityTypeList from './ActivityTypeList.vue';
import Emotion from '../Emotion.vue';
import Error from '../../partials/Error.vue';
import Participant from '../Participant.vue';

export default {
  components: {
    ActivityTypeList,
    Emotion,
    Error,
    Participant,
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
    contactId: {
      type: Number,
      default: 0,
    },
    name: {
      type: String,
      default: '',
    },
    activity: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
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
        contacts: [],
      },
      todayDate: '',
      initialEmotions: [],
      participants: [],
      errors: [],
    };
  },

  computed: {
    locale() {
      return this.$root.locale;
    },

    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  watch: {
    participants(value) {
      this.newActivity.contacts = _.map(value, p => p.id);
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.todayDate = moment().format('YYYY-MM-DD');
      this.resetFields();
    },

    updateDescription(updatedDescription) {
      this.newActivity.description = updatedDescription;
    },

    resetFields() {
      if (this.activity) {
        this.initialEmotions = JSON.parse(JSON.stringify(this.activity.emotions));
        this.newActivity.summary = this.activity.summary;
        this.newActivity.description = this.activity.description;
        this.newActivity.happened_at = this.activity.happened_at;
        this.updateEmotionsList(this.activity.emotions);
        this.newActivity.activity_type_id = this.activity.activity_type ? this.activity.activity_type.id : null;
        this.participants = this.activity.attendees.contacts.map(attendee => {
          return {
            id: attendee.id,
            name: attendee.complete_name,
          };
        });
      } else {
        this.initialEmotions = [];
        this.newActivity.summary = '';
        this.newActivity.description = '';
        this.newActivity.happened_at = this.todayDate;
        this.newActivity.emotions = [];
        this.newActivity.activity_type_id = null;
        this.participants = [];
      }
      this.displayDescription = this.newActivity.description ? this.newActivity.description !== '' : false;
      this.displayEmotions = this.newActivity.emotions && this.newActivity.emotions.length > 0;
      this.displayCategory = this.newActivity.activity_type_id !== null;
      this.displayParticipants = this.participants.length > 0;
      this.errors = [];
    },

    close() {
      this.resetFields();
      this.$emit('cancel');
    },

    store() {
      const method = this.activity ? 'put' : 'post';
      const url = this.activity ? 'activities/'+this.activity.id : 'activities';

      if (! this.newActivity.contacts.includes(this.contactId)) {
        this.newActivity.contacts.push(this.contactId);
      }

      axios[method](url, this.newActivity)
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
          this._errorHandle(error);
        });
    },

    updateParticipant: function (participants) {
      this.participants = participants;
    },

    updateEmotionsList: function(emotions) {
      // filter the list of emotions to populate a new array
      // containing only the emotion ids and not the entire objetcs
      this.newActivity.emotions = _.map(emotions, emotion => emotion.id);
    },

    _errorHandle(error) {
      if (error.response && typeof error.response.data === 'object') {
        this.errors = _.flatten(_.toArray(error.response.data));
      } else {
        this.errors = [this.$t('app.error_try_again'), error.message];
      }
    },
  }
};
</script>
