<style scoped>
</style>

<template>
  <div>
    <div>
      <img src="/img/people/tasks.svg" class="icon-section icon-tasks" />
      <h3>
        {{ $t('people.section_personal_tasks') }}

        <span v-if="tasks.length != 0" class="f6 pt2" :class="[ dirltr ? 'fr' : 'fl' ]">
          <a v-if="!editMode" class="pointer" cy-name="task-toggle-edit-mode" @click="editMode = true">
            {{ $t('app.edit') }}
          </a>
          <a v-else class="pointer" cy-name="task-toggle-edit-mode" @click="editMode = false">
            {{ $t('app.done') }}
          </a>
        </span>
      </h3>
    </div>

    <div :class="[editMode ? 'bg-washed-yellow b--yellow ba pa2' : '']">
      <!-- EMPTY STATE -->
      <div v-if="tasks.length == 0 && !addMode" class="tc bg-near-white b--moon-gray pa3" cy-name="task-blank-state">
        <p>{{ $t('people.tasks_blank_title') }}</p>
        <p>
          <a class="pointer" cy-name="add-task-button" @click="toggleAddMode">
            {{ $t('people.tasks_add_task') }}
          </a>
        </p>
      </div>

      <!-- LIST OF IN PROGRESS TASKS -->
      <ul>
        <li v-for="xtask in inProgress(tasks)" :key="xtask.id" :cy-name="'task-item-' + xtask.id">
          <input id="checkbox" v-model="xtask.completed" type="checkbox" class="mr1" @click="toggleComplete(xtask)" />
          {{ xtask.title }} <span v-if="xtask.description" class="silver ml3">
            {{ xtask.description }}
          </span>

          <div v-if="editMode" class="di">
            <i class="fa fa-pencil-square-o pointer pr2 ml3 dark-blue" @click="toggleEditMode(xtask)"></i>
            <i class="fa fa-trash-o pointer pr2 dark-blue" :cy-name="'task-delete-button-' + xtask.id" @click="trash(xtask)"></i>
          </div>

          <!-- EDIT BOX -->
          <form v-show="xtask.edit" class="bg-near-white pa2 br2 mt3 mb3">
            <div>
              <label class="db fw6 lh-copy f6">
                {{ $t('people.tasks_form_title') }}
              </label>
              <input v-model="xtask.title" class="pa2 db w-100" type="text" @keyup.esc="editMode = false" />
            </div>
            <div class="mt3">
              <label class="db fw6 lh-copy f6">
                {{ $t('people.tasks_form_description') }}
              </label>
              <textarea v-model="xtask.description" class="pa2 db w-100" type="text" @keyup.esc="editMode = false"></textarea>
            </div>
            <div class="lh-copy mt3">
              <a class="btn btn-primary" @click.prevent="update(xtask)">
                {{ $t('app.update') }}
              </a>
              <a class="btn" @click="toggleEditMode(xtask)">
                {{ $t('app.cancel') }}
              </a>
            </div>
          </form>
        </li>
      </ul>

      <!-- ADD TASK TO ENTER ADD MODE -->
      <div v-if="!updateMode && !addMode && tasks.length != 0" class="bg-near-white pa2 br2 mt3 mb3">
        <a class="pointer" @click="toggleAddMode">
          {{ $t('people.tasks_add_task') }}
        </a>
      </div>

      <!-- ADD A TASK VIEW -->
      <div v-if="addMode" cy-name="task-add-view">
        <form class="bg-near-white pa2 br2 mt3 mb3">
          <div>
            <label class="db fw6 lh-copy f6">
              {{ $t('people.tasks_form_title') }}
            </label>
            <input v-model="task.title" class="pa2 db w-100" type="text" cy-name="task-add-title" @keyup.esc="addMode = false" />
          </div>
          <div class="mt3">
            <label class="db fw6 lh-copy f6">
              {{ $t('people.tasks_form_description') }}
            </label>
            <textarea v-model="task.description" class="pa2 db w-100" type="text" @keyup.esc="addMode = false"></textarea>
          </div>
          <div class="lh-copy mt3">
            <a class="btn btn-primary" cy-name="save-task-button" @click.prevent="store">
              {{ $t('app.add') }}
            </a>
            <a class="btn" @click="addMode = false">
              {{ $t('app.cancel') }}
            </a>
          </div>
        </form>
      </div>

      <!-- LIST OF COMPLETED TASKS -->
      <ul>
        <li v-for="xtask in completed(tasks)" :key="xtask.id" class="f6" :cy-name="'task-item-completed-' + xtask.id">
          <input id="checkbox" v-model="xtask.completed" type="checkbox" class="mr1" @click="toggleComplete(xtask)" />
          <span class="light-silver mr1">
            {{ xtask.completed_at }}
          </span> <span class="moon-gray">
            {{ xtask.title }}
          </span> <span v-if="xtask.description" class="silver ml3">
            {{ xtask.description }}
          </span>

          <div v-if="editMode" class="di">
            <i class="fa fa-trash-o pointer pr2 ml3 dark-blue" @click="trash(xtask)"></i>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>

export default {

    props: ['hash', 'contactId'],
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
                return task.completed === true;
            });
        },

        inProgress: function (tasks) {
            return tasks.filter(function (task) {
                return task.completed === false;
            });
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
                    this.task.title = '';
                    this.addMode = false;
                    this.tasks.push(response.data);
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
                    this.updateMode = false;
                    this.toggleEditMode(task);
                    this.reinitialize();
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
                    this.tasks.splice(this.tasks.indexOf(task), 1);
                });

            if (this.tasks.length <= 1) {
                this.editMode = false;
            }
        },
    }
};
</script>
