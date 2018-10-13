<style scoped>
</style>

<template>
    <div>
        <ul v-for="document in documents" :key="document.id">
            <li>
                {{ document.original_filename }}
            </li>
            <li>
                {{ document.type }}
            </li>
            <li>
                {{ document.created_at }}
            </li>
        </ul>
    </div>
</template>

<script>
    export default {

        data() {
            return {
                documents: [],
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
                axios.get('/people/' + hash + '/documents')
                    .then(response => {
                        this.documents = response.data;
                    });
            },

            onRowClick(params) {
                window.location.href = params.row.route;
            }
        }
    }
</script>
