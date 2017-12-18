<style scoped>
</style>

<template>
  <div>

    <!-- Left columns: showing calendar -->
    <journal-calendar v-bind:journal-entry="journalEntry"></journal-calendar>

    <!-- Right column: showing logs -->
    <div class="fl journal-calendar-content">
      <div class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3 journal-line">
        <div class="flex">

          <!-- Day -->
          <div class="flex-none w-10 tc">
            <h3 class="mb0 normal fw5">{{ day.day }}</h3>
            <p class="mb0 black-60 f6">{{ day.day_name }}</p>
          </div>

          <!-- Log content -->
          <div class="flex-auto">
            <p class="mb2">You rated your day</p>

            {{ day.rate }}
            <!-- <ul class="f7">
              <li class="di">
                <a href="">Edit</a>
              </li>
              <li class="di">
                <a href="">Delete</a>
              </li>
            </ul> -->
          </div>

          <!-- Show comment -->
          <template v-if="day.comment">
            <div class="flex-none w-5">
              <div class="flex justify-center items-center h-100">
                <svg width="16px" height="13px" viewBox="0 0 16 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="flex-none">
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
                day: []
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
                this.day = this.journalEntry.object;
            },
        }
    }
</script>
