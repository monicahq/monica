<style scoped>
</style>

<template>
  <div>
    <div>
      <img src="/img/people/tasks.svg" class="icon-section icon-tasks">
      <h3>
        {{ $t('people.section_personal_tasks') }}

        <span class="fr f6 pt2" v-if="tasks.length != 0">
          <a class="pointer" @click="editMode = true" v-if="!editMode">{{ $t('app.edit') }}</a>
          <a class="pointer" @click="editMode = false" v-if="editMode">{{ $t('app.done') }}</a>
        </span>
      </h3>
    </div>

    <div v-bind:class="[editMode ? 'bg-washed-yellow b--yellow ba pa2' : '']">

      <!-- EMPTY STATE -->
      <div v-if="tasks.length == 0 && !addMode" class="tc bg-near-white b--moon-gray pa3">
        <p>{{ $t('people.tasks_blank_title') }}</p>
        <p><a class="pointer" @click="toggleAddMode">{{ $t('people.tasks_add_task') }}</a></p>
      </div>

      <!-- LIST OF IN PROGRESS TASKS -->
      <ul>
        <li v-for="task in inProgress(tasks)">
          <input type="checkbox" id="checkbox" v-model="task.completed" @click="toggleComplete(task)" class="mr1">
          {{ task.title }} <span class="silver ml3" v-if="task.description">{{ task.description }}</span>

          <div v-if="editMode" class="di">
            <i class="fa fa-pencil-square-o pointer pr2 ml3 dark-blue" @click="toggleEditMode(task)"></i>
            <i class="fa fa-trash-o pointer pr2 dark-blue" @click="trash(task)"></i>
          </div>

          <!-- EDIT BOX -->
          <form class="bg-near-white pa2 br2 mt3 mb3" v-show="task.edit">
            <div class="">
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
      <div v-if="addMode">
        <form class="bg-near-white pa2 br2 mt3 mb3">
          <div class="">
            <label class="db fw6 lh-copy f6">
              {{ $t('people.tasks_form_title') }}
            </label>
            <input class="pa2 db w-100" type="text" v-model="createForm.title" @keyup.esc="addMode = false">
          </div>
          <div class="mt3">
            <label class="db fw6 lh-copy f6">
              {{ $t('people.tasks_form_description') }}
            </label>
            <textarea class="pa2 db w-100" type="text" v-model="createForm.description" @keyup.esc="addMode = false"></textarea>
          </div>
          <div class="lh-copy mt3">
            <a @click.prevent="store" class="btn btn-primary">{{ $t('app.add') }}</a>
            <a class="btn" @click="addMode = false">{{ $t('app.cancel') }}</a>
          </div>
        </form>
      </div>

      <!-- LIST OF COMPLETED TASKS -->
      <ul>
        <li v-for="task in completed(tasks)" class="f6">
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
        /*
         * The component's data.
         */
        data() {
            return {
                tasks: [],

                updateMode: false,
                addMode: false,
                editMode: false,

                createForm: {
                    title: '',
                    description: '',
                    completed: 0
                },

                updateForm: {
                    id: 0,
                    title: '',
                    description: '',
                    completed: 0
                },
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

        props: ['contactId'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.getTasks();
            },

            reinitialize() {
                this.createForm.title = '';
                this.createForm.description = '';
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
                this.updateForm.id = task.id;
                this.updateForm.title = task.title;
                this.updateForm.description = task.description;
            },

            getTasks() {
                axios.get('/people/' + this.contactId + '/tasks')
                        .then(response => {
                            this.tasks = response.data;
                        });
            },

            store() {
                this.persistClient(
                    'post', '/people/' + this.contactId + '/tasks',
                    this.createForm
                );

                this.addMode = false;
            },

            toggleComplete(task) {
                axios.post('/people/' + this.contactId + '/tasks/' + task.id + '/toggle')
                        .then(response => {
                            this.getTasks();
                        });
            },

            update(task) {
                this.updateForm.id = task.id;
                this.updateForm.title = task.title;
                this.updateForm.description = task.description;
                this.updateForm.completed = task.completed;

                this.persistClient(
                    'put', '/people/' + this.contactId + '/tasks/' + task.id,
                    this.updateForm
                );

                this.updateMode = false;
            },

            trash(task) {
                this.updateForm.id = task.id;

                this.persistClient(
                    'delete', '/people/' + this.contactId + '/tasks/' + task.id,
                    this.updateForm
                );

                if (this.tasks.length <= 1) {
                    this.editMode = false;
                }
            },

            persistClient(method, uri, form) {
                form.errors = {};

                axios[method](uri, form)
                    .then(response => {
                        this.getTasks();
                    })
                    .catch(error => {
                        if (typeof error.response.data === 'object') {
                            form.errors = _.flatten(_.toArray(error.response.data));
                        } else {
                            form.errors = ['Something went wrong. Please try again.'];
                        }
                    });
            },
        }
    }
</script>
