<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>{{this.title}}</h5>
            </div>


            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Fetching...</span>
            </div>


            <div v-if="ready && !entry" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>No entry found.</span>
            </div>


            <div v-if="ready && entry">
                <slot :entry="entry"></slot>
            </div>
        </div>

    </div>
</template>

<script type="text/ecmascript-6">
    export default {
        props: {
            resource: {
                required: true,
                type: String,
                default: '',
            },
            title:  {
                required: true,
                type: String,
                default: '',
            },
            id: {
                required: true,
                type: Number,
                default: 0,
            },
        },


        /**
         * The component's data.
         */
        data() {
            return {
                entry: null,
                ready: false,
           };
        },


        watch: {
            id() {
                this.prepareEntry()
            }
        },


        /**
         * Prepare the component.
         */
        mounted() {
            this.prepareEntry()
        },


        methods: {
            prepareEntry() {
                document.title = this.title + " - Admin";
                this.ready = false;

                this.loadEntry((response) => {
                    this.entry = response.data.entry;

                    this.$parent.entry = response.data.entry;

                    this.ready = true;

                    this.updateEntry();
                });
            },


            loadEntry(after){
                axios.get('/admin-api/' + this.resource + '/' + this.id).then(response => {
                    if (_.isFunction(after)) {
                        after(response);
                    }
                }).catch(error => {
                    this.ready = true;
                })
            },

        }
    }
</script>
