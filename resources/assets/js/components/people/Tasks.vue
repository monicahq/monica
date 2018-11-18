<style scoped>
</style>

<template>
  <div>
    <div>
      <img src="/img/people/tasks.svg" class="icon-section icon-tasks">
      <h3>
        {{ $t('people.section_personal_tasks') }}

        <span class="f6 pt2" :class="[ dirltr ? 'fr' : 'fl' ]" v-if="tasks.length != 0">
          <a class="pointer" cy-name="task-toggle-edit-mode" @click="editMode = true" v-if="!editMode">{{ $t('app.edit') }}</a>
          <a class="pointer" cy-name="task-toggle-edit-mode" @click="editMode = false" v-else>{{ $t('app.done') }}</a>
        </span>
      </h3>
    </div>

    <div :class="[editMode ? 'bg-washed-yellow b--yellow ba pa2' : '']">

      <!-- EMPTY STATE -->
      <div v-if="tasks.length == 0 && !addMode" class="tc bg-near-white b--moon-gray pa3" cy-name="task-blank-state">
        <p>{{ $t('people.tasks_blank_title') }}</p>
        <p><a class="pointer" cy-name="add-task-button" @click="toggleAddMode">{{ $t('people.tasks_add_task') }}</a></p>
      </div>

      <!-- LIST OF IN PROGRESS TASKS -->
      <ul>
        <li v-for="task in inProgress(tasks)" :cy-name="'task-item-' + task.id" :key="task.id">
          <input type="checkbox" id="checkbox" v-model="task.completed" @click="toggleComplete(task)" class="mr1">
          {{ task.title }} <span class="silver ml3" v-if="task.description">{{ task.description }}</span>

          <div v-if="editMode" class="di">
            <i class="fa fa-pencil-square-o pointer pr2 ml3 dark-blue" @click="toggleEditMode(task)"></i>
            <i class="fa fa-trash-o pointer pr2 dark-blue" :cy-name="'task-delete-button-' + task.id" @click="trash(task)"></i>
          </div>

          <!-- EDIT BOX -->
          <form class="bg-near-white pa2 br2 mt3 mb3" v-show="task.edit">
            <div>
              <label class="db fw6 lh-copy f6">
                {{ $t('people.tasks_form_title') }}
              </label>
              <input class="pa2 db w-100" type="text" v-model="task.title" @keyup.esc="editMode = false">
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.tasks_form_description') }}
              </label>
              <textarea class="pa2 db w-100" type="text" v-model="task.description" @keyup.esc="editMode = false"></textarea>
            </div>
            <div class="lh-copy mt3">
              <a @click.prevent="update(task)" class="btn btn-primary">{{ $t('app.update') }}</a>
              <a class="btn" @click="toggleEditMode(task)">{{ $t('app.cancel') }}</a>
            </div>
          </form>
        </li>
      </ul>

      <!-- ADD TASK TO ENTER ADD MODE -->
      <div v-if="!updateMode && !addMode && tasks.length != 0" class="bg-near-white pa2 br2 mt3 mb3">
        <a class="pointer" @click="toggleAddMode">{{ $t('people.tasks_add_task') }}</a>
      </div>

      <!-- ADD A TASK VIEW -->
      <div v-if="addMode" cy-name="task-add-view">
        <form class="bg-near-white pa2 br2 mt3 mb3">
          <div>
            <label class="db fw6 lh-copy f6">
              {{ $t('people.tasks_form_title') }}
            </label>
            <input class="pa2 db w-100" type="text" cy-name="task-add-title" v-model="task.title" @keyup.esc="addMode = false">
          </div>
          <div class="mt3">
            <label class="db fw6 lh-copy f6">
              {{ $t('people.tasks_form_description') }}
            </label>
            <textarea class="pa2 db w-100" type="text" v-model="task.description" @keyup.esc="addMode = false"></textarea>
          </div>
          <div class="lh-copy mt3">
            <a @click.prevent="store" class="btn btn-primary" cy-name="save-task-button">{{ $t('app.add') }}</a>
            <a class="btn" @click="addMode = false">{{ $t('app.cancel') }}</a>
          </div>
        </form>
      </div>

      <!-- LIST OF COMPLETED TASKS -->
      <ul>
        <li v-for="task in completed(tasks)" class="f6" :cy-name="'task-item-completed-' + task.id" :key="task.id">
          <input type="checkbox" id="checkbox" v-model="task.completed" @click="toggleComplete(task)" class="mr1">
          <span class="light-silver mr1">{{ task.completed_at }}</span> <span class="moon-gray">{{ task.title }}</span> <span class="silver ml3" v-if="task.description">{{ task.description }}</span>

          <div v-if="editMode" class="di">
            <i class="fa fa-trash-o pointer pr2 ml3 dark-blue" @click="trash(task)"></i>
          </div>
        </li>
      </ul>
    </div>

  </div>
</template>

<script>

    export default {
        data() {
            return {
                tasks: [],

                updateMode: false,
                addMode: false,
                editMode: false,

                task: {
                    contact_id: 0,
                    title: '',
                    description: '',
                    completed: 0
                },

                dirltr: true,
            };
        },

        mounted() {
            this.prepareComponent();
        },

        props: ['hash', 'contactId'],

        methods: {
            prepareComponent() {
                this.dirltr = this.$root.htmldir == 'ltr';
                this.task.contact_id = this.contactId;
                this.index();
            },

            reinitialize() {
                this.task.title = '';
                this.task.description = '';
            },

            completed: function (tasks) {
                return tasks.filter(function (task) {
                    return task.completed === true
                })
            },

            inProgress: function (tasks) {
                return tasks.filter(function (task) {
                    return task.completed === false
                })
            },

            toggleAddMode() {
                this.addMode = true;
                this.reinitialize();
            },

            toggleEditMode(task) {
                Vue.set(task, 'edit', !task.edit);
            },

            index() {
                axios.get('/people/' + this.hash + '/tasks')
                        .then(response => {
                            this.tasks = response.data;
                        });
            },

            store() {
                axios.post('/tasks/', this.task)
                        .then(response => {
                            this.task.title = ''
                            this.addMode = false
                            this.tasks.push(response.data)
                            this.$notify({
                                group: 'main',
                                title: this.$t('app.default_save_success'),
                                text: '',
                                type: 'success'
                            });
                        });
            },

            update(task) {
                axios.put('/tasks/' + task.id, task)
                        .then(response => {
                            this.updateMode = false
                            this.toggleEditMode(task)
                            this.reinitialize()
                            this.$notify({
                                group: 'main',
                                title: this.$t('app.default_save_success'),
                                text: '',
                                type: 'success'
                            });
                        });
            },

            trash(task) {
                axios.delete('/tasks/' + task.id)
                        .then(response => {
                            this.tasks.splice(this.tasks.indexOf(task), 1)
                        });

                if (this.tasks.length <= 1) {
                    this.editMode = false;
                }
            },
        }
    }
</script>
