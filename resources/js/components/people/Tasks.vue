<style scoped>
</style>

<template>
  <div>
    <div>
      <img src="img/people/tasks.svg" :alt="$t('people.tasks_title')" class="icon-section icon-tasks" />
      <h3>
        {{ $t('people.section_personal_tasks') }}

        <span v-if="tasks.length != 0" class="f6 pt2" :class="[ dirltr ? 'fr' : 'fl' ]">
          <a v-if="!editMode" v-cy-name="'task-toggle-edit-mode'" class="pointer" href="" @click.prevent="editMode = true">
            {{ $t('app.edit') }}
          </a>
          <a v-else v-cy-name="'task-toggle-edit-mode'" class="pointer" href="" @click.prevent="editMode = false">
            {{ $t('app.done') }}
          </a>
        </span>
      </h3>
    </div>

    <div :class="[editMode ? 'bg-washed-yellow b--yellow ba pa2' : '']">
      <!-- EMPTY STATE -->
      <div v-if="tasks.length == 0 && !addMode" v-cy-name="'task-blank-state'" class="tc bg-near-white b--moon-gray pa3">
        <p>{{ $t('people.tasks_blank_title') }}</p>
        <p>
          <a v-cy-name="'add-task-button'" class="pointer" href="" @click.prevent="toggleAddMode">
            {{ $t('people.tasks_add_task') }}
          </a>
        </p>
      </div>

      <!-- LIST OF IN PROGRESS TASKS -->
      <ul v-cy-name="'tasks-body'" v-cy-items="inProgress(tasks).map(t => t.id)">
        <li v-for="(task, i) in inProgress(tasks)" :key="task.id" v-cy-name="'task-item-' + task.id">
          <form-checkbox
            v-model.lazy="task.completed"
            :disabled="task.disabled"
            :name="'task'"
            :dclass="[ dirltr ? 'mr1' : 'ml1' ]"
            @change="toggleComplete(task)"
          >
            {{ task.title }}
            <span v-if="task.description" class="silver ml3" dir="auto">
              {{ task.description }}
            </span>
          </form-checkbox>

          <div v-if="editMode" class="di">
            <em class="fa fa-pencil-square-o pointer pr2 ml3 dark-blue" @click="toggleEditMode(task)"></em>
            <em v-cy-name="'task-delete-button-' + task.id" class="fa fa-trash-o pointer pr2 dark-blue" @click="trash(task)"></em>
          </div>

          <!-- EDIT BOX -->
          <form v-show="task.edit" class="bg-near-white pa2 br2 mt3 mb3">
            <div>
              <label :for="'edit-title' + i" class="db fw6 lh-copy f6">
                {{ $t('people.tasks_form_title') }}
              </label>
              <input :id="'edit-title' + i" v-model="task.title" class="pa2 db w-100" type="text" @keyup.esc="editMode = false" />
            </div>
            <div class="mt3">
              <label :for="'edit-description' + i" class="db fw6 lh-copy f6">
                {{ $t('people.tasks_form_description') }}
              </label>
              <textarea :id="'edit-description' + i"
                        v-model="task.description"
                        class="pa2 db w-100"
                        type="text"
                        @keyup.esc="editMode = false"
              >
              </textarea>
            </div>
            <div class="lh-copy mt3">
              <a class="btn btn-primary" href="" @click.prevent="update(task, true)">
                {{ $t('app.update') }}
              </a>
              <a class="btn" href="" @click.prevent="toggleEditMode(task)">
                {{ $t('app.cancel') }}
              </a>
            </div>
          </form>
        </li>
      </ul>

      <!-- ADD TASK TO ENTER ADD MODE -->
      <div v-if="!updateMode && !addMode && tasks.length != 0" class="bg-near-white pa2 br2 mt3 mb3">
        <a class="pointer" href="" @click.prevent="toggleAddMode">
          {{ $t('people.tasks_add_task') }}
        </a>
      </div>

      <!-- ADD A TASK VIEW -->
      <div v-if="addMode" v-cy-name="'task-add-view'">
        <form class="bg-near-white pa2 br2 mt3 mb3">
          <div>
            <label for="add-title" class="db fw6 lh-copy f6">
              {{ $t('people.tasks_form_title') }}
            </label>
            <input id="add-title" v-model="newTask.title" v-cy-name="'task-add-title'" class="pa2 db w-100" type="text"
                   @keyup.esc="addMode = false"
            />
          </div>
          <div class="mt3">
            <label for="add-description" class="db fw6 lh-copy f6">
              {{ $t('people.tasks_form_description') }}
            </label>
            <textarea id="add-description" v-model="newTask.description" class="pa2 db w-100" type="text" @keyup.esc="addMode = false"></textarea>
          </div>
          <div class="lh-copy mt3">
            <a v-cy-name="'save-task-button'" class="btn btn-primary" href="" @click.prevent="store">
              {{ $t('app.add') }}
            </a>
            <a class="btn" href="" @click.prevent="addMode = false">
              {{ $t('app.cancel') }}
            </a>
          </div>
        </form>
      </div>

      <!-- LIST OF COMPLETED TASKS -->
      <ul>
        <li v-for="task in completed(tasks)" :key="task.id" v-cy-name="'task-item-completed-' + task.id" class="f6">
          <form-checkbox
            v-model.lazy="task.completed"
            :disabled="task.disabled"
            :name="'checkbox'"
            :dclass="[ dirltr ? 'mr1' : 'ml1' ]"
            @change="toggleComplete(task)"
          >
            <span class="light-silver mr1">
              {{ task.completed_at }}
            </span>
            <span class="moon-gray" dir="auto">
              {{ task.title }}
            </span>
            <span v-if="task.description" class="silver ml3" dir="auto">
              {{ task.description }}
            </span>
          </form-checkbox>
          <div v-if="editMode" class="di">
            <em class="fa fa-trash-o pointer pr2 ml3 dark-blue" @click="trash(task)"></em>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {

  props: {
    hash: {
      type: String,
      default: '',
    },
    contactId: {
      type: Number,
      default: -1,
    },
  },

  data() {
    return {
      tasks: [],

      updateMode: false,
      addMode: false,
      editMode: false,

      newTask: {
        contact_id: 0,
        title: '',
        description: '',
        completed: 0
      },
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.newTask.contact_id = this.contactId;
    this.index();
  },

  methods: {
    reinitialize() {
      this.newTask.title = '';
      this.newTask.description = '';
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
      axios.get('people/' + this.hash + '/tasks')
        .then(response => {
          this.tasks = _.map(response.data, function (task) {
            return _.assign({}, task, {disabled: false});
          });
        });
    },

    store() {
      axios.post('tasks', this.newTask)
        .then(response => {
          this.addMode = false;
          this.reinitialize();
          this.tasks.push(response.data);
          this.$notify({
            group: 'main',
            title: this.$t('app.default_save_success'),
            text: '',
            type: 'success'
          });
        });
    },

    toggleComplete(task) {
      this.updateMode = true;
      Vue.set(task, 'disabled', true);
      this.update(task, false);
    },

    update(task, toggleEdit) {
      axios.put('tasks/' + task.id, task)
        .then(response => {
          this.updateMode = false;
          Vue.set(task, 'disabled', false);
          Vue.set(task, 'completed_at', response.data.completed_at ? this.formatDate(response.data.completed_at): null);
          if (toggleEdit) {
            this.toggleEditMode(task);
          }
          this.$notify({
            group: 'main',
            title: this.$t('app.default_save_success'),
            text: '',
            type: 'success'
          });
        });
    },

    formatDate(dateAsString) {
      let moment = require('moment-timezone');
      moment.locale(this._i18n.locale);
      moment.tz.setDefault('UTC');

      var date = moment.tz(moment(dateAsString), this.$root.timezone);

      return date.format('ll');
    },

    trash(task) {
      axios.delete('tasks/' + task.id)
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
