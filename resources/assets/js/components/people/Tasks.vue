<style scoped>
</style>

<template>
  <div>
    <ul>
      <li v-for="tasks in task">
        <input type="checkbox" id="checkbox" v-model="task.completed">
        <label for="checkbox">{{ task.title }}</label>
      </li>
    </ul>
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
                contactFieldTypes: [],

                editMode: false,
                addMode: false
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

            getTasks() {
                axios.get('/people/' + this.contactId + '/tasks')
                        .then(response => {
                            this.tasks = response.data;
                        });
            },

            persistClient(method, uri, form) {
                form.errors = {};

                axios[method](uri, form)
                    .then(response => {
                        this.getContactInformationData();
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
