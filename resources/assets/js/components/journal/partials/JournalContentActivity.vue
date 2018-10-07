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
    <journal-calendar :journal-entry="journalEntry"></journal-calendar>

    <!-- Right column: showing logs -->
    <div :class="[ dirltr ? 'fl' : 'fr' ]" class="journal-calendar-content">
      <div class="br3 ba b--gray-monica bg-white mb3 journal-line">
        <!-- Actual log -->
        <div class="flex pb3 pt3">
          <!-- Day -->
          <div class="flex-none w-10 tc">
            <h3 class="mb0 normal fw5">{{ activity.day }}</h3>
            <p class="mb0 black-60 f6">{{ activity.day_name }}</p>
          </div>

          <!-- Log content -->
          <div class="flex-auto">
            <p class="mb1">
              <span class="pr2 f6 avenir">{{ $t('journal.journal_entry_type_activity') }}: {{ activity.activity_type }}</span>
            </p>
            <p class="mb1">{{ activity.summary }}</p>

            <p v-if="showDescription">{{ activity.description }}</p>
          </div>

          <!-- Comment -->
          <template v-if="activity.description">
            <div class="flex-none w-5 pointer" @click="toggleDescription()">
              <div class="flex justify-center items-center h-100">
                <svg width="16px" height="13px" viewBox="0 0 16 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="flex-none" v-tooltip.top="'Show comment'">
                  <defs></defs>
                  <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="square">
                      <g id="Desktop" transform="translate(-839.000000, -279.000000)" stroke="#979797">
                          <g id="Group-4" transform="translate(839.000000, 278.000000)">
                              <path d="M0.5,1.5 L15.5,1.5" id="Line-2"></path>
                              <path d="M0.5,9.5 L15.5,9.5" id="Line-2"></path>
                              <path d="M0.5,5.5 L13.5,5.5" id="Line-2"></path>
                              <path d="M0.5,13.5 L10.5,13.5" id="Line-2"></path>
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
            <p class="mb0 f6 gray">{{ $t('journal.journal_created_automatically') }}</p>
          </div>

          <!-- Avatars of the attendees -->
          <div class="flex-auto w-60 tr mt2 pa1 pr3 pb2">
            <span class="f6 gray">{{ $t('app.with') }} </span>
            <div v-for="attendees in activity.attendees" class="dib pointer ml2" @click="redirect(attendees)">
              <img v-if="attendees.information.avatar.has_avatar" :src="attendees.information.avatar.avatar_url" class="br3 journal-avatar-small" v-tooltip="attendees.complete_name" />
              <img v-else-if="attendees.information.avatar.gravatar_url" :src="attendees.information.avatar.gravatar_url" class="br3 journal-avatar-small" v-tooltip.bottom="attendees.complete_name" />
              <div v-else v-tooltip="attendees.complete_name" :style="{ 'background-color': attendees.information.avatar.default_avatar_color }" class="br3 white tc journal-initial-small">
                {{ attendees.initials }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                showDescription: false,
                activity: [],

                dirltr: true,
            };
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        props: ['journalEntry'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.dirltr = this.$root.htmldir == 'ltr';
                // not necessary, just a way to add more clarity to the code
                this.activity = this.journalEntry.object;
            },

            toggleDescription() {
                this.showDescription = !this.showDescription
            },

            redirect(attendee) {
                window.location.href = "/people/" + attendee.hash_id
            }
        }
    }
</script>
