<style scoped>
</style>

<template>
  <div>
    <div class="">
      <h3 class="mb2">
        🍿 {{ $t('people.activity_title') }}

        <span class="fr relative" style="top: -7px;">
          <a v-if="displayLogCall == false" class="btn edit-information" @click="displayLogCall = true">
            {{ $t('people.modal_call_title') }}
          </a>
          <a v-if="displayLogCall" class="btn edit-information" @click="displayLogCall = false">
            {{ $t('app.cancel') }}
          </a>
        </span>
      </h3>
    </div>

    <!-- BLANK STATE -->
    <div v-if="!displayLogCall && activities.length == 0" class="w-100">
      <div class="bg-near-white tc pa3 br2 ba b--light-gray">
        <p>{{ $t('people.activities_blank_title', { name: name }) }}</p>
        <a class="pointer" @click.prevent="displayLogCall = true">
          {{ $t('people.activities_blank_add_activity') }}
        </a>
      </div>
    </div>

    <!-- LOG AN ACTIVITY -->
    <transition name="fade">
      <div v-if="displayLogCall" class="ba br3 mb3 pa3 b--black-40">
        <div class="dt dt--fixed pb3 mb3 mb0-ns">
          <!-- WHEN -->
          <div class="dtc pr2">
            <p class="mb2 b">
              {{ $t('people.modal_call_exact_date') }}
            </p>
            <div class="di mr3">
              <div class="dib">
                <form-date
                  v-model="newCall.called_at"
                  :default-date="todayDate"
                  :locale="'en'"
                  @selected="updateDate($event)"
                />
              </div>
            </div>
          </div>

          <!-- WHO CALLED -->
          <div class="dtc">
            <p class="mb2 b">
              {{ $t('people.modal_call_who_called') }}
            </p>
            <div class="di mr3">
              <input id="you" v-model="newCall.contact_called" type="radio" class="mr1" name="contact_called"
                     :value="false"
              />
              <label for="you" class="pointer">
                {{ $t('people.call_you_called') }}
              </label>
            </div>
            <div class="di mr3">
              <input id="contact" v-model="newCall.contact_called" type="radio" class="mr1" name="contact_called"
                     :value="true"
              />
              <label for="contact" class="pointer">
                {{ $t('people.call_he_called', { name : name }) }}
              </label>
            </div>
          </div>
        </div>

        <!-- CONTENT -->
        <div>
          <label class="b">
            {{ $t('people.modal_call_comment') }}
          </label>
          <form-textarea
            v-model="newCall.content"
            :required="true"
            :no-label="true"
            :rows="4"
            :placeholder="$t('people.conversation_add_content')"
            @contentChange="updateContent($event)"
          />
          <p class="f6">
            {{ $t('app.markdown_description') }} <a href="https://guides.github.com/features/mastering-markdown/" target="_blank">
              {{ $t('app.markdown_link') }}
            </a>
          </p>
        </div>

        <!-- EMOTIONS -->
        <div class="bb b--gray-monica pb3">
          <label class="b">
            {{ $t('people.modal_call_emotion') }}
          </label>
          <emotion class="pv2" @updateEmotionsList="updateEmotionsList" />
        </div>

        <!-- ACTIONS -->
        <div class="pt3">
          <div class="flex-ns justify-between">
            <div class="">
              <a class="btn btn-secondary tc w-auto-ns w-100 mb2 pb0-ns" @click.prevent="displayLogCall = false; resetFields()">
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

      <!-- ADDITIONAL INFORMATION -->
      <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
        <div class="w-70" :class="[ dirltr ? 'fl' : 'fr' ]">
          <span :class="[ dirltr ? 'mr3' : 'ml3' ]">
            {{ activity.happened_at | moment }}
          </span>
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
      activities: [],
      dirltr: true,
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

    store() {
      axios.post('/people/' + this.hash + '/activities', this.newCall)
        .then(response => {
          this.getactivities();
          this.resetFields();
          this.displayLogCall = false;
          this.chosenEmotions = [];

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

    showEditBox(call) {
      this.editCallId = call.id;
      this.editCall.content = call.content;
      this.editCall.contact_called = call.contact_called;
      this.editCall.called_at = moment.utc(call.called_at).format('YYYY-MM-DD');
    },

    showDestroyCall(call) {
      this.destroyCallId = call.id;
    },

    destroyCall(call) {
      axios.delete('/people/' + this.hash + '/activities/' + this.destroyCallId)
        .then(response => {
          this.activities.splice(this.activities.indexOf(call), 1);
        });
    },

    updateEmotionsList: function(emotions) {
      this.chosenEmotions = emotions;
      this.newCall.emotions = [];
      this.editCall.emotions = [];

      // filter the list of emotions to populate a new array
      // containing only the emotion ids and not the entire objetcs
      for (let i = 0; i < this.chosenEmotions.length; i++) {
        this.newCall.emotions.push(this.chosenEmotions[i].id);
        this.editCall.emotions.push(this.chosenEmotions[i].id);
      }
    }
  }
};
</script>
