<style scoped>
</style>

<template>
  <div>
    <!-- LIST OF TASKS -->
    <ul>
      <li v-for="task in tasks">
        <input type="checkbox" id="checkbox" v-model="task.completed" v-if="!task.archived">
        <label for="checkbox">{{ task.title }}</label>
        <div v-if="task.description" class="ml2">
          {{ task.description }}
        </div>
      </li>
    </ul>

    <!-- ADD TASK TO ENTER ADD MODE -->
    <div v-if="!editMode && !addMode">
      <a class="pointer" @click="toggleAdd">Add a task</a>
    </div>

    <!-- ADD A TASK VIEW -->
    <div v-if="addMode">
      <form class="bg-near-white pa2">
        <div class="">
          <label class="db fw6 lh-copy f6">
            Title
          </label>
          <input class="pa2 db w-100" type="text" v-model="createForm.title" @keyup.esc="addMode = false">
        </div>
        <div class="mt3">
          <label class="db fw6 lh-copy f6">
            Description (optional)
          </label>
          <textarea class="pa2 db w-100" type="text" v-model="createForm.description" @keyup.esc="addMode = false"></textarea>
        </div>
        <div class="lh-copy mt3">
          <a @click.prevent="store" class="btn btn-primary">{{ trans('app.add') }}</a>
          <a class="btn" @click="addMode = false">{{ trans('app.cancel') }}</a>
        </div>
      </form>
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

                editMode: false,
                addMode: false,

                createForm: {
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

            toggleAdd() {
                this.addMode = true;
                this.reinitialize();
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
