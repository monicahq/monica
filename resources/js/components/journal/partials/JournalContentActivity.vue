<style scoped>
  .journal-avatar-small {
    height: 25px;
    width: 25px;
    font-size: 13px;
  }

  .journal-initial-small {
    height: 25px;
    width: 25px;
    font-size: 13px;
    padding-top: 2px;
  }
</style>

<template>
  <div>
    <!-- Left columns: showing calendar -->
    <journal-calendar :journal-entry="journalEntry" />

    <!-- Right column: showing logs -->
    <div :class="[ dirltr ? 'fl' : 'fr' ]" class="journal-calendar-content">
      <div class="br3 ba b--gray-monica bg-white mb3 journal-line">
        <!-- Actual log -->
        <div class="flex pb3 pt3">
          <!-- Day -->
          <div class="flex-none w-10 tc">
            <h3 class="mb0 normal fw5">
              {{ activity.day }}
            </h3>
            <p class="mb0 black-60 f6">
              {{ activity.day_name }}
            </p>
          </div>

          <!-- Log content -->
          <div class="flex-auto">
            <p class="mb1">
              <span class="pr2 f6 avenir">
                {{ $t('journal.journal_entry_type_activity') }}: {{ activity.activity_type }}
              </span>
            </p>
            <p class="mb1">
              {{ activity.summary }}
            </p>

            <p v-if="showDescription" class="markdown" v-html="compiledMarkdown(activity.description)">
            </p>
          </div>

          <!-- Comment -->
          <template v-if="activity.description">
            <div class="flex-none w-5 pointer" @click="toggleDescription()">
              <div class="flex justify-center items-center h-100">
                <svg v-tooltip.top="$t('journal.journal_show_comment')" width="16px" height="13px" viewBox="0 0 16 13" version="1.1"
                     xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="flex-none"
                >
                  <defs />
                  <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"
                     stroke-linecap="square"
                  >
                    <g id="Desktop" transform="translate(-839.000000, -279.000000)" stroke="#979797">
                      <g id="Group-4" transform="translate(839.000000, 278.000000)">
                        <path id="Line-2" d="M0.5,1.5 L15.5,1.5" />
                        <path id="Line-2" d="M0.5,9.5 L15.5,9.5" />
                        <path id="Line-2" d="M0.5,5.5 L13.5,5.5" />
                        <path id="Line-2" d="M0.5,13.5 L10.5,13.5" />
                      </g>
                    </g>
                  </g>
                </svg>
              </div>
            </div>
          </template>
        </div>

        <!-- Edit/Delete/Attendees -->
        <div class="flex bt b--gray-monica">
          <div class="w-10">
            &nbsp;
          </div>

          <div class="flex-none w-30 mt2 pt1 pb2">
            <p class="mb0 f6 gray">
              {{ $t('journal.journal_created_automatically') }}
            </p>
          </div>

          <!-- Avatars of the attendees -->
          <div class="flex-auto w-60 tr mt2 pa1 pr3 pb2">
            <span class="f6 gray">
              {{ $t('app.with') }}
            </span>
            <div v-for="attendees in activity.attendees" :key="attendees.id" class="dib pointer ml2" @click="redirect(attendees)">
              <img v-tooltip.bottom="attendees.complete_name" :src="attendees.information.avatar.url" class="br3 journal-avatar-small" :alt="attendees.complete_name" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {

  props: {
    journalEntry: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      showDescription: false,
      activity: [],
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
      // not necessary, just a way to add more clarity to the code
      this.activity = this.journalEntry.object;
    },

    toggleDescription() {
      this.showDescription = !this.showDescription;
    },

    redirect(attendee) {
      window.location.href = 'people/' + attendee.hash_id;
    },

    compiledMarkdown (text) {
      return marked(text, { sanitize: true });
    }
  }
};
</script>
