<style scoped>
</style>

<template>
    <div class="">
        <div class="" v-for="lifeEvent in lifeEvents">
            <p>{{ lifeEvent.life_event_type }}</p>
            <p>{{ lifeEvent.name }}</p>
            <p>{{ lifeEvent.note }}</p>
            <p>{{ lifeEvent.happened_at }}</p>
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
                lifeEvents: [],
            };
        },

        props: ['hash'],

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent()
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent()
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.getLifeEvents()
            },

            getLifeEvents() {
                axios.get('/people/' + this.hash + '/lifeevents')
                        .then(response => {
                            this.lifeEvents = response.data;
                        });
            },
        }
    }
</script>
