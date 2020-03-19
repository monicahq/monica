<style scoped>
.btn-title {
  top: -7px;
}
</style>

<template>
  <div>
    <div class="">
      <h3 class="mb2">
        🍿 {{ $t('people.activity_title') }}

        <span class="fr relative btn-title">
          <a v-if="displayLogActivity == false" v-cy-name="'add-activity-button'" class="btn edit-information" @click="displayLogActivity = true">
            {{ $t('people.activities_add_activity') }}
          </a>
          <a v-else class="btn edit-information" @click="displayLogActivity = false">
            {{ $t('app.cancel') }}
          </a>
        </span>
      </h3>
    </div>

    <!-- BLANK STATE -->
    <div v-if="!displayLogActivity && activities.length == 0" class="w-100">
      <div v-cy-name="'activities-blank-state'" class="bg-near-white tc pa3 br2 ba b--light-gray">
        <p>{{ $t('people.activities_blank_title', { name: name }) }}</p>
        <a class="pointer" href="" @click.prevent="displayLogActivity = true">
          {{ $t('people.activities_blank_add_activity') }}
        </a>
      </div>
    </div>

    <!-- LOG AN ACTIVITY -->
    <template v-if="displayLogActivity">
      <create-activity
        :hash="hash"
        :contact-id="contactId"
        :name="name"
        @update="updateList($event)"
        @cancel="displayLogActivity = false"
      />
    </template>

    <!-- LIST OF ACTIVITIES -->
    <div v-cy-name="'activities-body'" v-cy-items="activities.map(c => c.id)">
      <div v-for="activity in activities" :key="activity.id" v-cy-name="'activity-body-'+activity.id" class="ba br2 b--black-10 br--top w-100 mb2">
        <template v-if="!activity.edit">
          <h2 class="pl2 pr2 pt3 f5">
            {{ activity.summary }}
          </h2>

          <div v-if="activity.description" dir="auto" class="markdown pl2 pr2 pb3" v-html="compiledMarkdown(activity.description)">
          </div>

          <!-- DETAILS -->
          <div class="pa2 cf bt b--black-10 br--bottom f7">
            <div class="w-70" :class="[ dirltr ? 'fl' : 'fr' ]">
              <ul class="list">
                <!-- HAPPENED AT -->
                <li class="di" :class="[ dirltr ? 'mr3' : 'ml3' ]">
                  {{ activity.happened_at | moment }}
                </li>

                <!-- PARTICIPANT LIST -->
                <li v-if="activity.attendees.total > 1" class="di">
                  <ul class="di list" :class="[ dirltr ? 'mr3' : 'ml3' ]">
                    <li class="di">
                      {{ $t('people.activities_list_participants') }}
                    </li>
                    <li v-for="attendee in activity.attendees.contacts.filter(c => c.id !== contactId)" :key="attendee.id" class="di mr2">
                      <a :href="'people/' + attendee.hash_id">{{ attendee.complete_name }}</a>
                    </li>
                  </ul>
                </li>

                <!-- EMOTIONS LIST -->
                <li v-if="activity.emotions.length != 0" class="di">
                  <ul class="di list" :class="[ dirltr ? 'mr3' : 'ml3' ]">
                    <li class="di">
                      {{ $t('people.activities_list_emotions') }}
                    </li>
                    <li v-for="emotion in activity.emotions" :key="emotion.id" class="di">
                      {{ $t('app.emotion_' + emotion.name) }}
                    </li>
                  </ul>
                </li>

                <!-- ACTIVITY TYPE -->
                <li v-if="activity.activity_type" class="di" :class="[ dirltr ? 'mr3' : 'ml3' ]">
                  {{ activity.activity_type.name }}
                </li>
              </ul>
            </div>
            <div class="w-30" :class="[ dirltr ? 'fl tr' : 'fr tl' ]">
              <!-- ACTIONS -->
              <ul class="list">
                <li class="di">
                  <a v-cy-name="'edit-activity-button-'+activity.id" href="" class="pointer" @click.prevent="$set(activity, 'edit', true)">{{ $t('app.edit') }}</a>
                  <a v-show="destroyActivityId != activity.id" v-cy-name="'delete-activity-button-'+activity.id" href="" class="pointer" @click.prevent="showDestroyActivity(activity)">{{ $t('app.delete') }}</a>
                  <ul v-show="destroyActivityId == activity.id" class="di">
                    <li class="di">
                      <a v-cy-name="'confirm-delete-activity'" class="pointer red" @click.prevent="destroyActivity(activity)">
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
        </template>

        <!-- EDIT THE CURRENT ACTIVITY -->
        <create-activity v-else
                         :hash="hash"
                         :name="name"
                         :activity="activity"
                         :contact-id="contactId"
                         @update="updateList($event)"
                         @cancel="$set(activity, 'edit', false); displayLogActivity = false"
        />
      </div>
    </div>

    <p v-if="activities.length > 0" class="tc">
      📗 <a :href="'people/' + hash + '/activities/summary'">{{ $t('people.activities_view_activities_report') }}</a>
    </p>
  </div>
</template>

<script>
import moment from 'moment';
import CreateActivity from './CreateActivity.vue';

export default {
  components: {
    CreateActivity
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
      displayDescription: false,
      displayEmotions: false,
      displayCategory: false,
      displayParticipants: false,
      destroyActivityId: 0,
      errors: [],
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
      this.getActivities();
      this.todayDate = moment().format('YYYY-MM-DD');
    },

    compiledMarkdown (text) {
      return marked(text, { sanitize: true });
    },

    getActivities() {
      axios.get('api/contacts/' + this.contactId + '/activities')
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
      axios.delete('api/activities/' + activity.id)
        .then(response => {
          this.activities.splice(this.activities.indexOf(activity), 1);
        });
    },
  }
};
</script>
