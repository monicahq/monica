<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>{{this.title}}</h5>

            <!--
            <input type="text" class="form-control w-25"
                   v-if="tag || entries.length > 0"
                   id="searchInput"
                   placeholder="Search Tag" v-model="tag" @input.stop="search">
            -->
        </div>

        <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
            </svg>

            <span>Fetching...</span>
        </div>

        <div v-else>
            <slot :entries="entries" :total="total"></slot>
        </div>

    </div>
</template>

<script type="text/ecmascript-6">
    export default {
        props: {
            resource: {
                type: String,
                default: null,
            },
            title: {
                type: String,
                default: null,
            },
            debounceWait: {
                type: Number,
                default: 200,
            },
        },


        /**
         * The component's data.
         */
        data() {
            return {
                entries: [],
                total: 0,
                ready: false,
                serverParams: {
                    search: '',
                    page: 1,
                    perPage: 30
                },
                searchEntries: null,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = this.title + " - Admin";

            this._focusOnSearch();

            this.searchEntries = _.debounce(() => {
                this._loadNewEntries();
            }, this.debounceWait);

            this._loadNewEntries();
        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            document.onkeyup = null;
        },


        methods: {
            updateParams(newProps) {
              this.serverParams = Object.assign({}, this.serverParams, newProps);
            },

            _loadEntries(after){
                axios.post('/admin-api/' + this.resource,
                    this.serverParams
                ).then(response => {
                    if (_.isFunction(after)) {
                        after(
                            _.uniqBy(response.data.entries, entry => _.uniqueId()),
                            response.data.total
                        );
                    }
                })
            },

            /**
             * Load new entries.
             */
            _loadNewEntries(){
                this._loadEntries((entries, total) => {
                    this.entries = entries;
                    this.total = total;
                    this.ready = true;
                });
            },

            search() {
                this.searchEntries();
            },

            /**
             * Focus on the search input when "/" key is hit.
             */
            _focusOnSearch(){
                document.onkeyup = event => {
                    if (event.which === 191 || event.keyCode === 191) {
                        let searchInput = document.getElementById("searchInput");

                        if (searchInput) {
                            searchInput.focus();
                        }
                    }
                };
            }
        }
    }
</script>
