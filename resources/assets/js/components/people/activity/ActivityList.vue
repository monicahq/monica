<style scoped>
</style>

<template>
  <div>
    <div class="">
      <h3 class="mb2">
        🍿 {{ $t('people.activity_title') }}

        <span class="fr relative" style="top: -7px;">
          <a v-if="displayLogActivity == false" class="btn edit-information" @click="displayLogActivity = true">
            {{ $t('people.activities_add_activity') }}
          </a>
          <a v-if="displayLogActivity" class="btn edit-information" @click="displayLogActivity = false">
            {{ $t('app.cancel') }}
          </a>
        </span>
      </h3>
    </div>

    <!-- BLANK STATE -->
    <div v-if="!displayLogActivity && activities.length == 0" class="w-100">
      <div class="bg-near-white tc pa3 br2 ba b--light-gray">
        <p>{{ $t('people.activities_blank_title', { name: name }) }}</p>
        <a class="pointer" @click.prevent="displayLogActivity = true">
          {{ $t('people.activities_blank_add_activity') }}
        </a>
      </div>
    </div>

    <!-- LOG AN ACTIVITY -->
    <create-activity
      :hash="hash"
      :name="name"
      :display-log-activity="displayLogActivity"
      v-on:update="updateList($event)"
      v-on:cancel="displayLogActivity = false" />

    <!-- LIST OF ACTIVITIES -->
    <div v-for="activity in activities" :key="activity.id" class="ba br2 b--black-10 br--top w-100 mb2">
      <h2 class="pl2 pr2 pt3 f5">{{ activity.summary }}</h2>

      <div v-html="activity.description" class="pl2 pr2 pb3" v-if="activity.description">
      </div>

      <!-- DETAILS -->
      <div class="pa2 cf bt b--black-10 br--bottom f7">
        <div class="w-70" :class="[ dirltr ? 'fl' : 'fr' ]">
          <ul class="list">
            <!-- HAPPENED AT -->
            <li :class="[ dirltr ? 'mr3 di' : 'ml3 di' ]">
              {{ activity.happened_at | moment }}
            </li>

            <!-- PARTICIPANT LIST -->
            <li v-if="activity.attendees.total > 1" class="di">
              <ul :class="[ dirltr ? 'mr3 di list' : 'ml3 di list' ]">
                <li class="di">{{ $t('people.activities_list_participants') }}</li>
                <li v-for="attendee in activity.attendees.contacts" :key="attendee.id" class="di mr2">
                  <span v-show="attendee.id != contactId">{{ attendee.complete_name }}</span>
                </li>
              </ul>
            </li>

            <!-- EMOTIONS LIST -->
            <li v-if="activity.emotions.length != 0" class="di">
              <ul :class="[ dirltr ? 'mr3 di list' : 'ml3 di list' ]">
                <li class="di">{{ $t('people.activities_list_emotions') }}</li>
                <li v-for="emotion in activity.emotions" :key="emotion.id" class="di">
                  {{ $t('app.emotion_' + emotion.name) }}
                </li>
              </ul>
            </li>

            <!-- ACTIVITY TYPE -->
            <li :class="[ dirltr ? 'mr3 di' : 'ml3 di' ]" v-if="activity.activity_type">
              {{ activity.activity_type.name }}
            </li>
          </ul>
        </div>
        <div class="w-30" :class="[ dirltr ? 'fl tr' : 'fr tl' ]">
          <!-- ACTIONS -->
          <ul class="list">
            <li class="di">
              <a v-show="destroyActivityId != activity.id" class="pointer" @click.prevent="showDestroyActivity(activity)">{{ $t('app.delete') }}</a>
              <ul v-show="destroyActivityId == activity.id" class="di">
                <li class="di">
                  <a class="pointer red" @click.prevent="destroyActivity(activity)">
                    {{ $t('app.delete_confirm') }}
                  </a>
                </li>
                <li class="di">
                  <a class="pointer mr1" @click.prevent="destroyActivityId = 0">
                    {{ $t('app.cancel') }}
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>

    </div>

    <p class="tc" v-if="activities.length > 0">📗 <a :href="'people/' + hash + '/activities/summary'">{{ $t('people.activities_view_activities_report') }}</a></p>
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
    contactId: {
      type: Number,
      default: 0,
    }
  },

  data() {
    return {
      displayLogActivity: false,
      activities: [],
      emotions: [],
      dirltr: true,
      displayDescription: false,
      displayEmotions: false,
      displayCategory: false,
      displayParticipants: false,
      destroyActivityId: 0,
      errors: [],
    };
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.dirltr = this.$root.htmldir == 'ltr';
      this.getActivities();
      this.todayDate = moment().format('YYYY-MM-DD');
    },

    compiledMarkdown (text) {
      return marked(text, { sanitize: true });
    },

    getActivities() {
      axios.get('/people/' + this.hash + '/activities')
        .then(response => {
          this.activities = response.data.data;
        });
    },

    updateList: function (activity) {
      this.displayLogActivity = false;
      this.getActivities();
    },

    showDestroyActivity(activity) {
      this.destroyActivityId = activity.id;
    },

    destroyActivity(activity) {
      axios.delete('/people/' + this.hash + '/activities/' + activity.id)
        .then(response => {
          this.activities.splice(this.activities.indexOf(activity), 1);
        });
    },
  }
};
</script>
