<style scoped>
</style>

<template>
  <div>
    <div class="">
      <h3 class="mb2">
        ☎️ {{ $t('people.call_title') }}

        <span class="fr relative" style="top: -7px;">
          <a v-if="displayLogCall == false" class="btn edit-information" href="" @click.prevent="displayLogCall = true">
            {{ $t('people.modal_call_title') }}
          </a>
          <a v-if="displayLogCall" class="btn edit-information" href="" @click.prevent="displayLogCall = false">
            {{ $t('app.cancel') }}
          </a>
        </span>
      </h3>
    </div>

    <!-- BLANK STATE -->
    <div v-if="!displayLogCall && calls.length == 0" class="w-100">
      <div class="bg-near-white tc pa3 br2 ba b--light-gray">
        <p>{{ $t('people.call_blank_title', { name: name }) }}</p>
        <a class="pointer" href="" @click.prevent="displayLogCall = true">
          {{ $t('people.modal_call_title') }}
        </a>
      </div>
    </div>

    <!-- LOG A CALL -->
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
                  :locale="locale"
                />
              </div>
            </div>
          </div>

          <!-- WHO CALLED -->
          <div class="dtc">
            <p class="mb2 b">
              {{ $t('people.modal_call_who_called') }}
            </p>
            <div class="dt">
              <div class="dt-row">
                <form-radio
                  v-model="newCall.contact_called"
                  :name="'contact_called'"
                  :value="false"
                  :iclass="'mr1'"
                  :dclass="'dtc mr3'"
                >
                  {{ $t('people.call_you_called') }}
                </form-radio>
                <form-radio
                  v-model="newCall.contact_called"
                  :name="'contact_called'"
                  :value="true"
                  :iclass="'mr1'"
                  :dclass="'dtc mr3'"
                >
                  {{ $t('people.call_he_called', { name : name }) }}
                </form-radio>
              </div>
            </div>
          </div>
        </div>

        <!-- CONTENT -->
        <div>
          <form-textarea
            v-model="newCall.content"
            :required="true"
            :label="$t('people.modal_call_comment')"
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
              <a class="btn btn-secondary tc w-auto-ns w-100 mb2 pb0-ns" href="" @click.prevent="displayLogCall = false; resetFields()">
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

    <!-- LIST OF CALLS -->
    <div v-for="call in calls" :key="call.id" class="ba br2 b--black-10 br--top w-100 mb2">
      <div v-show="editCallId != call.id" class="pa2">
        <span v-if="!call.content">
          {{ $t('people.call_blank_desc', { name: call.contact.first_name }) }}
        </span>
        <span v-if="call.content" v-html="compiledMarkdown(call.content)"></span>
      </div>

      <!-- INLINE UPDATE DIV -->
      <div v-show="editCallId == call.id" class="pa2">
        <div>
          <div>
            <form-textarea
              v-model="editCall.content"
              :label="$t('people.modal_call_comment')"
              rows="4"
              iclass="br2 f5 w-100 ba b--black-40 pa2 outline-0"
              @contentChange="updateEditCallContent($event)"
            />
            <p class="f6">
              {{ $t('app.markdown_description') }}
            </p>
          </div>

          <!-- WHO CALLED -->
          <div class="pb3 mb3 mb0-ns">
            <p class="mb2">
              {{ $t('people.modal_call_who_called') }}
            </p>
            <div class="di mr3">
              <input :id="'you' + call.id" v-model="editCall.contact_called" type="radio" class="mr1" :name="'contact_called' + call.id"
                     :value="false"
              />
              <label :for="'you' + call.id" class="pointer">
                {{ $t('people.call_you_called') }}
              </label>
            </div>
            <div class="di mr3">
              <input :id="'contact' + call.id" v-model="editCall.contact_called" type="radio" class="mr1" :name="'contact_called' + call.id"
                     :value="true"
              />
              <label :for="'contact' + call.id" class="pointer">
                {{ $t('people.call_he_called', { name : name }) }}
              </label>
            </div>
          </div>

          <!-- EMOTIONS -->
          <div class="bb b--gray-monica pb3 mb3">
            <label class="b">
              {{ $t('people.modal_call_emotion') }}
            </label>
            <emotion class="pv2" :initial-emotions="call.emotions" @updateEmotionsList="updateEmotionsList" />
          </div>

          <!-- ACTIONS -->
          <div class="">
            <div class="flex-ns justify-between">
              <div class="">
                <a class="btn btn-secondary tc w-auto-ns w-100 mb2 pb0-ns" href="" @click.prevent="editCallId = 0">
                  {{ $t('app.cancel') }}
                </a>
              </div>
              <div class="">
                <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" @click.prevent="update()">
                  {{ $t('app.update') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ADDITIONAL INFORMATION -->
      <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
        <div class="w-70" :class="[ dirltr ? 'fl' : 'fr' ]">
          <span :class="[ dirltr ? 'mr3' : 'ml3' ]">
            {{ call.called_at | moment }}
          </span>
          <span :class="[ dirltr ? 'mr3' : 'ml3' ]">
            {{ call.contact_called ? $t('people.call_he_called', { name : name }) : $t('people.call_you_called') }}
          </span>
          <span v-if="call.emotions.length != 0">
            <span :class="[ dirltr ? 'mr2' : 'ml2' ]">
              {{ $t('people.call_emotions') }}
            </span>
            <ul class="di">
              <li v-for="emotion in call.emotions" :key="emotion.id" class="di">
                {{ $t('app.emotion_' + emotion.name) }}
              </li>
            </ul>
          </span>
        </div>

        <div :class="[ dirltr ? 'fl tr' : 'fr tl' ]" class="w-30">
          <a :class="[ dirltr ? 'mr2' : 'ml2' ]" class="pointer " href="" @click.prevent="showEditBox(call)">
            {{ $t('app.update') }}
          </a>
          <a v-show="destroyCallId != call.id" class="pointer" href="" @click.prevent="showDestroyCall(call)">
            {{ $t('app.delete') }}
          </a>
          <ul v-show="destroyCallId == call.id" class="di">
            <li class="di">
              <a class="pointer mr1" href="" @click.prevent="destroyCallId = 0">
                {{ $t('app.cancel') }}
              </a>
            </li>
            <li class="di">
              <a class="pointer red" href="" @click.prevent="destroyCall(call)">
                {{ $t('app.delete_confirm') }}
              </a>
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
      calls: [],
      displayLogCall: false,
      todayDate: '',
      editCallId: 0,
      destroyCallId: 0,
      chosenEmotions: [],
      newCall: {
        content: '',
        called_at: '',
        contact_called: false,
        emotions: [],
      },
      editCall: {
        content: '',
        contact_called: false,
        emotions: [],
      }
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
    this.prepareComponent(this.hash);
  },

  methods: {
    prepareComponent(hash) {
      this.getCalls();
      this.todayDate = moment().format('YYYY-MM-DD');
      this.newCall.called_at = this.todayDate;
    },

    compiledMarkdown (text) {
      return marked(text, { sanitize: true });
    },

    resetFields() {
      this.newCall.content = '';
      this.newCall.called_at = this.todayDate;
    },

    getCalls() {
      axios.get('people/' + this.hash + '/calls')
        .then(response => {
          this.calls = response.data.data;
        });
    },

    store() {
      axios.post('people/' + this.hash + '/calls', this.newCall)
        .then(response => {
          this.getCalls();
          this.resetFields();
          this.displayLogCall = false;
          this.chosenEmotions = [];

          this.$notify({
            group: 'main',
            title: this.$t('people.calls_add_success'),
            text: '',
            type: 'success'
          });
        });
    },

    update() {
      axios.put('people/' + this.hash + '/calls/' + this.editCallId, this.editCall)
        .then(response => {
          this.getCalls();
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

    updateContent(updatedContent) {
      this.newCall.content = updatedContent;
    },

    updateEditCallContent(updatedContent) {
      this.editCall.content = updatedContent;
    },

    updateDate(updatedContent) {
      this.newCall.called_at = updatedContent;
    },

    showDestroyCall(call) {
      this.destroyCallId = call.id;
    },

    destroyCall(call) {
      axios.delete('people/' + this.hash + '/calls/' + this.destroyCallId)
        .then(response => {
          this.calls.splice(this.calls.indexOf(call), 1);
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
