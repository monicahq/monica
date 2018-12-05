<style scoped>
</style>

<template>
        <div>
            <div class="ba br2 b--black-10 br--top w-100 mb4" v-for="call in calls" v-bind:key="call.id">
                <div class="pa2">
                    <span v-if="!call.content">{{ $t('people.call_blank_desc', { name: call.contact.firstname }) }}</span>
                    <span v-if="call.content">{{ call.content }}</span>
                </div>
                <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
                    <div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-50">
                        {{ call.date }}
                    </div>
                    <div class="{{ htmldir() == 'ltr' ? 'fl tr' : 'fr tl' }} w-50">
                        <a class="pointer">
                            Delete
                        </a>
                    </div>
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
                calls: [],
            };
        },

        props: {
            hash: {
                type: String,
            },
        },

        mounted() {
            this.prepareComponent(this.hash)
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent(hash) {
                axios.get('/people/' + hash + '/calls')
                    .then(response => {
                        this.calls = response.data;
                    });
            },

            onRowClick(params) {
                window.location.href = params.row.route;
            }
        }
    }
</script>
