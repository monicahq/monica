<style scoped>
</style>

<template>
    <div>
        <notifications group="archive" position="top middle" duration=5000 width="400" />

        <a href="#" class="btn edit-information" @click="toggle">{{ active ? $t('people.contact_archive') : $t('people.contact_unarchive') }}</a>
    </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
            };
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        props: {
            hash: {
                type: String,
            },
            active: {
                type: Boolean,
            },
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            },

            toggle() {
                axios.put('/people/' + this.hash + '/archive')
                    .then(response => {
                        this.active = response.data.is_active

                        this.$notify({
                            group: 'archive',
                            title: this.$t('app.default_save_success'),
                            text: '',
                            type: 'success'
                        });
                    });
            },
        }
    }
</script>
