<style scoped>
</style>

<template>
    <div class="custom-fields">
        <div class="mw7 center br3 ba b--gray-monica bg-white mb5">

            <!-- Name -->
            <div class="pa4-ns ph3 pv2 bb b--gray-monica">
                <div class="mb3 mb0-ns">
                    <form-input
                        v-bind:input-type="'text'"
                        v-bind:id="'name'"
                        v-bind:required="true"
                        v-bind:title="'Name'">
                    </form-input>
                    <p>Test</p>
                </div>
            </div>

            <!-- Is it a list or not -->
            <div class="pa4-ns ph3 pv2 bb b--gray-monica">
                <div class="mb3 mb0-ns">
                    <input type="radio" id="" v-model="selectedOption" name="birthdate" value="approximate">
                    Allow multiple entries
                </div>
                <div class="mb3 mb0-ns">
                    <input type="radio" id="" v-model="selectedOption" name="birthdate" value="approximate">
                    Limit to one entry only
                </div>
            </div>

            <!-- Add a new field -->
            <div class="pa4-ns ph3 pv2 bb b--gray-monica">
                <div class="mb3 mb0-ns">
                    <form-select
                        :options="customFieldTypes"
                        v-bind:required="true"
                        v-bind:title="'Add a field'"
                        v-bind:id="'field'">
                    </form-select>
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
                modules: [],
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

        props: ['customFieldTypes'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.dirltr = $('html').attr('dir') == 'ltr';
                this.getModules();
            },

            getModules() {
                axios.get('/settings/personalization/modules')
                        .then(response => {
                            this.modules = response.data;
                        });
            },

            toggle(module) {
                axios.post('/settings/personalization/modules/' + module.id)
                        .then(response => {
                            this.$notify({
                                  group: 'main',
                                  title: response.data,
                                  text: '',
                                  type: 'success'
                              });
                        });
            }
        }
    }
</script>
