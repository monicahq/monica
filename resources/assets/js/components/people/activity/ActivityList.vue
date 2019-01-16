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
          <a v-if="displayLogActivity" class="btn edit-information" @click="resetFields()">
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
    <transition name="fade">
      <div v-if="displayLogActivity" class="ba br3 mb3 pa3 b--black-40">
        <div class="dt dt--fixed pb3 mb3 mb0-ns bb b--gray-monica">
          <!-- SUMMARY -->
          <div class="dtc pr2">
            <p class="mb2 b">
              What did you do?
            </p>
            <form-input
              v-model="newActivity.summary"
              :id="'last_name'"
              :input-type="'text'"
              :required="false">
            </form-input>
          </div>

          <!-- WHEN -->
          <div class="dtc">
            <p class="mb2 b">
              The activity happened on
            </p>
            <div class="di">
              <div class="dib">
                <form-date
                  v-model="newActivity.happened_at"
                  :default-date="todayDate"
                  :locale="'en'"
                  @selected="updateDate($event)"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- ADDITIONAL FIELDS -->
        <div class="bb b--gray-monica pv3 mb3" v-show="!displayDescription || !displayEmotions || !displayCategory">
          <ul class="list">
            <li class="di pointer mr3" v-show="!displayDescription"><a @click="displayDescription = true">Add more details</a></li>
            <li class="di pointer mr3" v-show="!displayEmotions"><a @click="displayEmotions = true">Add emotions</a></li>
            <li class="di pointer" v-show="!displayCategory"><a @click="displayCategory = true">Indicate a category</a></li>
            <li class="di pointer" v-show="!displayParticipants"><a @click="displayParticipants = true">Add participants</a></li>
          </ul>
        </div>

        <!-- DESCRIPTION -->
        <div class="bb b--gray-monica pv3 mb3" v-show="displayDescription">
          <label>
            {{ $t('people.modal_call_comment') }}
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
            {{ $t('app.markdown_description') }} <a href="https://guides.github.com/features/mastering-markdown/" target="_blank">
              {{ $t('app.markdown_link') }}
            </a>
          </p>
        </div>

        <!-- EMOTIONS -->
        <div class="bb b--gray-monica pb3 mb3" v-show="displayEmotions">
          <label>
            Do you want to log how you felt during this activity? (optional)
          </label>
          <emotion class="pv2" @updateEmotionsList="updateEmotionsList" />
        </div>

        <!-- ACTIVITY CATEGORIES -->
        <div class="bb b--gray-monica pb3" v-show="displayCategory">
          <label>
            {{ $t('people.activities_add_pick_activity') }}
          </label>
          <activity-type-list v-on:change="updateCategory($event)" />
        </div>

        <!-- PARTICPANTS -->
        <div class="bb b--gray-monica pb3" v-show="displayParticipants">
          <label>
            Who participated in this activity?
          </label>
          <participant-list v-on:change="updateParticipant($event)" />
        </div>

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

    <!-- LIST OF ACTIVITIES -->
    <div v-for="activity in activities" :key="activity.id" class="ba br2 b--black-10 br--top w-100 mb2">
      {{ activity.summary }} {{ activity.description }}

      <!-- DETAILS -->
      <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
        <div class="w-70" :class="[ dirltr ? 'fl' : 'fr' ]">
          <ul class="list">
            <li :class="[ dirltr ? 'mr3 di' : 'ml3 di' ]">
              {{ activity.happened_at | moment }}
            </li>
            <li :class="[ dirltr ? 'mr3 di' : 'ml3 di' ]" v-if="activity.activity_type">
              {{ activity.activity_type.name }}
            </li>
            <li :class="[ dirltr ? 'mr3 di' : 'ml3 di' ]">
              <a :class="[ dirltr ? 'mr2' : 'ml2' ]" class="pointer " @click.prevent="showEditBox(activity)">
                {{ $t('app.update') }}
              </a>
              <a v-show="destroyActivityId != activity.id" class="pointer" @click.prevent="showDestroyActivity(activity)">
                {{ $t('app.delete') }}
              </a>
              <ul v-show="destroyActivityId == activity.id" class="di">
                <li class="di">
                  <a class="pointer mr1" @click.prevent="destroyActivityId = 0">
                    {{ $t('app.cancel') }}
                  </a>
                </li>
                <li class="di">
                  <a class="pointer red" @click.prevent="destroyActivity(activity)">
                    {{ $t('app.delete_confirm') }}
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import moment from 'moment';

export default {

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
  },

  data() {
    return {
      displayLogActivity: false,
      activities: [],
      dirltr: true,
      displayDescription: false,
      displayEmotions: false,
      displayCategory: false,
      displayParticipants: false,
      newActivity: {
        summary: '',
        description: '',
        happened_at: '',
        emotions: [],
        activity_type_id: 0,
      },
      destroyActivityId: 0,
    };
  },

  mounted() {
    this.prepareComponent(this.hash);
  },

  methods: {
    prepareComponent(hash) {
      this.dirltr = this.$root.htmldir == 'ltr';
      this.getActivities();
      this.todayDate = moment().format('YYYY-MM-DD');
      this.newActivity.happened_at = this.todayDate;
    },

    resetFields() {
      this.displayDescription = false;
      this.displayEmotions = false;
      this.displayCategory = false;
      this.displayParticipants = false;
      this.newActivity.summary = '';
      this.description = '';
      this.happened_at = '';
      this.emotions = [];
      this.activity_type_id = 0;
      this.displayLogActivity = false;
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
          this.displayLogActivity = false;
          this.getActivities();
          this.chosenEmotions = [];
          this.resetFields();

          this.$notify({
            group: 'main',
            title: this.$t('people.activities_add_success'),
            text: '',
            type: 'success'
          });
        });
    },

    update() {
      axios.put('/people/' + this.hash + '/activities/' + this.editCallId, this.editCall)
        .then(response => {
          this.getactivities();
          this.editCallId = 0;
          this.chosenEmotions = [];

          this.$notify({
            group: 'main',
            title: this.$t('app.default_save_success'),
            text: '',
            type: 'success'
          });
        });
    },

    showEditBox(activity) {
      this.editCallId = call.id;
      this.editCall.content = call.content;
      this.editCall.contact_called = call.contact_called;
      this.editCall.called_at = moment.utc(call.called_at).format('YYYY-MM-DD');
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

    updateEmotionsList: function(emotions) {
      this.chosenEmotions = emotions;
      this.newActivity.emotions = [];
      this.editCall.emotions = [];

      // filter the list of emotions to populate a new array
      // containing only the emotion ids and not the entire objetcs
      for (let i = 0; i < this.chosenEmotions.length; i++) {
        this.newActivity.emotions.push(this.chosenEmotions[i].id);
        this.editCall.emotions.push(this.chosenEmotions[i].id);
      }
    }
  }
};
</script>
