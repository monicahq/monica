<style scoped>
</style>

<template>
  <div class="br3 ba b--gray-monica bg-white mb4">

    <notifications group="main" position="bottom right" width="400" />

    <div class="pa3 bb b--gray-monica tc">
      <ul>
        <li @click.prevent="setActiveTab('calls')" v-bind:class="[activeTab == 'calls' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']">
          {{ $t('dashboard.tab_recent_calls') }}
        </li>
        <li @click.prevent="setActiveTab('notes')" v-bind:class="[activeTab == 'notes' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']">
          {{ $t('dashboard.tab_favorite_notes') }}
        </li>
        <li @click.prevent="setActiveTab('debts')" v-bind:class="[activeTab == 'debts' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']">
          {{ $t('dashboard.tab_debts') }}
        </li>
        <li @click.prevent="setActiveTab('tasks')" v-bind:class="[activeTab == 'tasks' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']">
          {{ $t('dashboard.tab_tasks') }}
        </li>
      </ul>
    </div>
    <div class="pa3">

      <!-- Calls -->
      <div v-if="activeTab == 'calls'">
        <ul v-if="calls.length != 0">
          <li class="pb2" v-for="call in calls" v-bind:key="call.id">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 473.806 473.806" style="enable-background:new 0 0 473.806 473.806;" xml:space="preserve" width="15px" height="15px" class="mr2">
              <g>
                <g>
                  <path d="M374.456,293.506c-9.7-10.1-21.4-15.5-33.8-15.5c-12.3,0-24.1,5.3-34.2,15.4l-31.6,31.5c-2.6-1.4-5.2-2.7-7.7-4    c-3.6-1.8-7-3.5-9.9-5.3c-29.6-18.8-56.5-43.3-82.3-75c-12.5-15.8-20.9-29.1-27-42.6c8.2-7.5,15.8-15.3,23.2-22.8    c2.8-2.8,5.6-5.7,8.4-8.5c21-21,21-48.2,0-69.2l-27.3-27.3c-3.1-3.1-6.3-6.3-9.3-9.5c-6-6.2-12.3-12.6-18.8-18.6    c-9.7-9.6-21.3-14.7-33.5-14.7s-24,5.1-34,14.7c-0.1,0.1-0.1,0.1-0.2,0.2l-34,34.3c-12.8,12.8-20.1,28.4-21.7,46.5    c-2.4,29.2,6.2,56.4,12.8,74.2c16.2,43.7,40.4,84.2,76.5,127.6c43.8,52.3,96.5,93.6,156.7,122.7c23,10.9,53.7,23.8,88,26    c2.1,0.1,4.3,0.2,6.3,0.2c23.1,0,42.5-8.3,57.7-24.8c0.1-0.2,0.3-0.3,0.4-0.5c5.2-6.3,11.2-12,17.5-18.1c4.3-4.1,8.7-8.4,13-12.9    c9.9-10.3,15.1-22.3,15.1-34.6c0-12.4-5.3-24.3-15.4-34.3L374.456,293.506z M410.256,398.806    C410.156,398.806,410.156,398.906,410.256,398.806c-3.9,4.2-7.9,8-12.2,12.2c-6.5,6.2-13.1,12.7-19.3,20    c-10.1,10.8-22,15.9-37.6,15.9c-1.5,0-3.1,0-4.6-0.1c-29.7-1.9-57.3-13.5-78-23.4c-56.6-27.4-106.3-66.3-147.6-115.6    c-34.1-41.1-56.9-79.1-72-119.9c-9.3-24.9-12.7-44.3-11.2-62.6c1-11.7,5.5-21.4,13.8-29.7l34.1-34.1c4.9-4.6,10.1-7.1,15.2-7.1    c6.3,0,11.4,3.8,14.6,7c0.1,0.1,0.2,0.2,0.3,0.3c6.1,5.7,11.9,11.6,18,17.9c3.1,3.2,6.3,6.4,9.5,9.7l27.3,27.3    c10.6,10.6,10.6,20.4,0,31c-2.9,2.9-5.7,5.8-8.6,8.6c-8.4,8.6-16.4,16.6-25.1,24.4c-0.2,0.2-0.4,0.3-0.5,0.5    c-8.6,8.6-7,17-5.2,22.7c0.1,0.3,0.2,0.6,0.3,0.9c7.1,17.2,17.1,33.4,32.3,52.7l0.1,0.1c27.6,34,56.7,60.5,88.8,80.8    c4.1,2.6,8.3,4.7,12.3,6.7c3.6,1.8,7,3.5,9.9,5.3c0.4,0.2,0.8,0.5,1.2,0.7c3.4,1.7,6.6,2.5,9.9,2.5c8.3,0,13.5-5.2,15.2-6.9    l34.2-34.2c3.4-3.4,8.8-7.5,15.1-7.5c6.2,0,11.3,3.9,14.4,7.3c0.1,0.1,0.1,0.1,0.2,0.2l55.1,55.1    C420.456,377.706,420.456,388.206,410.256,398.806z" fill="#8c8c8c"/>
                  <path d="M256.056,112.706c26.2,4.4,50,16.8,69,35.8s31.3,42.8,35.8,69c1.1,6.6,6.8,11.2,13.3,11.2c0.8,0,1.5-0.1,2.3-0.2    c7.4-1.2,12.3-8.2,11.1-15.6c-5.4-31.7-20.4-60.6-43.3-83.5s-51.8-37.9-83.5-43.3c-7.4-1.2-14.3,3.7-15.6,11    S248.656,111.506,256.056,112.706z" fill="#8c8c8c"/>
                  <path d="M473.256,209.006c-8.9-52.2-33.5-99.7-71.3-137.5s-85.3-62.4-137.5-71.3c-7.3-1.3-14.2,3.7-15.5,11    c-1.2,7.4,3.7,14.3,11.1,15.6c46.6,7.9,89.1,30,122.9,63.7c33.8,33.8,55.8,76.3,63.7,122.9c1.1,6.6,6.8,11.2,13.3,11.2    c0.8,0,1.5-0.1,2.3-0.2C469.556,223.306,474.556,216.306,473.256,209.006z" fill="#8c8c8c"/>
                </g>
              </g>
            </svg>
            <span class="black-50 mr1 f6">{{ call.called_at }}</span> <span class="mr1 black-50">•</span>
            <a :href="'/people/' + call.contact_id">{{ call.name }}</a>
          </li>
        </ul>

        <!-- Calls: Blank state -->
        <div class="tc mt4 mb4" v-if="calls.length == 0">
          <p>{{ $t('dashboard.tab_calls_blank') }}</p>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="activeTab == 'notes'">
        <div class="pb3 cf" v-for="note in notes" v-if="notes.length != 0" v-bind:key="note.id">
          <div class="fl w-10 avatars">
            <avatar v-bind:contact="note.contact" v-bind:clickable="true"></avatar>
          </div>
          <div class="pl3 fl w-90">
            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="15px" height="15px" viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve">
              <g>
                  <path fill="none" d="M397.997,102.5H383.5v18.686c0,6.921-5.582,12.53-12.5,12.53s-12.5-5.608-12.5-12.53V102.5h-17v18.686
                      c0,6.921-5.582,12.53-12.5,12.53c-6.916,0-12.5-5.608-12.5-12.53V102.5h-117v18.686c0,6.921-5.581,12.53-12.5,12.53
                      c-6.919,0-12.5-5.608-12.5-12.53V102.5h-17v18.686c0,6.921-5.581,12.53-12.5,12.53s-12.5-5.608-12.5-12.53V102.5h-14.321
                      c-4.613,0-10.679,1.544-10.679,6.157v325.76c0,4.612,6.065,11.083,10.679,11.083h279.818c4.613,0,6.503-6.471,6.503-11.083v-325.76
                      C404.5,104.044,402.611,102.5,397.997,102.5z M383.5,386.5h-251v-16h251V386.5z M383.5,345.5h-251v-17h251V345.5z M383.5,303.5
                      h-251v-17h251V303.5z M383.5,261.5h-251v-17h251V261.5z M383.5,219.5h-251v-17h251V219.5z"/>
                  <rect x="132.5" y="244.5" width="251" height="17"/>
                  <rect x="132.5" y="202.5" width="251" height="17"/>
                  <rect x="132.5" y="286.5" width="251" height="17"/>
                  <rect x="132.5" y="328.5" width="251" height="17"/>
                  <rect x="132.5" y="370.5" width="251" height="16"/>
                  <path d="M397.997,85.5H383.5V62.717c0-6.919-5.582-12.529-12.5-12.529s-12.5,5.61-12.5,12.529V85.5h-17V62.717
                      c0-6.919-5.582-12.529-12.5-12.529c-6.916,0-12.5,5.61-12.5,12.529V85.5h-117V62.717c0-6.919-5.581-12.529-12.5-12.529
                      c-6.919,0-12.5,5.61-12.5,12.529V85.5h-17V62.717c0-6.919-5.581-12.529-12.5-12.529s-12.5,5.61-12.5,12.529V85.5h-14.321
                      c-13.838,0-27.679,9.318-27.679,23.157v325.76c0,13.839,13.84,28.083,27.679,28.083h279.818c13.839,0,23.503-14.244,23.503-28.083
                      v-325.76C421.5,94.818,411.836,85.5,397.997,85.5z M404.5,434.417c0,4.612-1.89,11.083-6.503,11.083H118.179
                      c-4.613,0-10.679-6.471-10.679-11.083v-325.76c0-4.613,6.065-6.157,10.679-6.157H132.5v18.686c0,6.921,5.581,12.53,12.5,12.53
                      s12.5-5.608,12.5-12.53V102.5h17v18.686c0,6.921,5.581,12.53,12.5,12.53c6.919,0,12.5-5.608,12.5-12.53V102.5h117v18.686
                      c0,6.921,5.584,12.53,12.5,12.53c6.918,0,12.5-5.608,12.5-12.53V102.5h17v18.686c0,6.921,5.582,12.53,12.5,12.53
                      s12.5-5.608,12.5-12.53V102.5h14.497c4.613,0,6.503,1.544,6.503,6.157V434.417z"/>
              </g>
            </svg>
            <span class="black-50 mr1 f6">{{ note.created_at }}</span>
            <span class="mr1 black-50">•</span>
            <a :href="'/people/' + note.contact.id">{{ note.name }}</a>
            <p>
              {{ note.body }}
            </p>
          </div>
        </div>

        <!-- Notes: Blank state -->
        <div class="tc mt4 mb4" v-if="notes.length == 0">
          <p>{{ $t('dashboard.notes_title') }}</p>
        </div>
      </div>

      <!-- Debts -->
      <div v-if="activeTab == 'debts'">
        <ul v-if="debts.length != 0">
          <li class="pb2" v-for="debt in debts" v-bind:key="debt.id">
            <span class="black-50 mr1 f6">{{ debt.created_at | formatDate }}</span>
            <span class="mr1 black-50">•</span>
            <a :href="'/people/' + debt.contact.hash_id">{{ debt.contact.first_name }}</a>
            <span class="mr1 black-50">•</span>
            {{ debt.amount_with_currency }}
            <span class="black-50 f6" v-if="debt.in_debt == 'yes'">
              <span class="mr1 black-50">•</span>{{ $t('dashboard.debts_you_owe') }}
            </span>
          </li>
        </ul>

        <!-- Debts: Blank state -->
        <div class="tc mt4 mb4" v-if="debts.length == 0">
          <p>{{ $t('dashboard.tab_debts_blank') }}</p>
        </div>
      </div>

      <!-- Tasks -->
      <div v-if="activeTab == 'tasks'">
        <ul v-if="tasks.length != 0">
          <li class="pb0" v-for="task in tasks" v-bind:key="task.id">
            <label class="pointer mb0">
              <input type="checkbox" @click="toggleComplete(task)">
              {{ task.title }}
            </label>
            <span class="black-50 mr1 f7">
              {{ task.created_at | formatDate }}
              <a :href="'/people/' + task.contact.hash_id">{{ task.contact.first_name }}</a>
            </span>
          </li>
        </ul>

        <!-- Tasks: Blank state -->
        <div class="tc mt4 mb4" v-if="tasks.length == 0">
          <p>{{ $t('dashboard.tab_tasks_blank') }}</p>
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
              activeTab: '',

              callsAlreadyLoaded: false,
              notesAlreadyLoaded: false,
              debtsAlreadyLoaded: false,
              tasksAlreadyLoaded: false,

              calls: [],
              notes: [],
              debts: [],
              tasks: [],
            };
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        props: ['defaultActiveTab'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.setActiveTab(this.defaultActiveTab);
            },

            setActiveTab(view) {
                this.activeTab = view;

                this.saveTab(view);

                if (view == 'calls') {
                    if (! this.callsAlreadyLoaded) {
                        this.getCalls();
                        this.callsAlreadyLoaded = true;
                    }
                }

                if (view == 'notes') {
                    if (! this.notesAlreadyLoaded) {
                        this.getNotes();
                        this.notesAlreadyLoaded = true;
                    }
                }

                if (view == 'debts') {
                    if (! this.debtsAlreadyLoaded) {
                        this.getDebts();
                        this.debtsAlreadyLoaded = true;
                    }
                }

                if (view == 'tasks') {
                    if (! this.tasksAlreadyLoaded) {
                        this.getTasks();
                        this.tasksAlreadyLoaded = true;
                    }
                }
            },

            saveTab(view) {
                axios.post('/dashboard/setTab', {'tab':view})
                        .then(response => {
                        });
            },

            getCalls() {
                axios.get('/dashboard/calls')
                        .then(response => {
                            this.calls = response.data;
                        });
            },

            getNotes() {
                axios.get('/dashboard/notes')
                        .then(response => {
                            this.notes = response.data;
                        });
            },

            getDebts() {
                axios.get('/dashboard/debts')
                        .then(response => {
                            this.debts = response.data;
                        });
            },

            getTasks() {
                axios.get('/dashboard/tasks')
                        .then(response => {
                            this.tasks = response.data;
                        });
            },

            toggleComplete(task) {
                axios.post('/people/' + task.contact.hash_id + '/tasks/' + task.id + '/toggle')
                        .then(response => {
                            this.$notify({
                              group: 'main',
                              title: this.$t('app.default_save_success'),
                              text: '',
                              type: 'success'
                          });
                        });
            },
        }
    }
</script>
