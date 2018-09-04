<style scoped>
.life-event-list-icon {
    border-radius: 50%;
    background-color: #e6ebf1;
    width: 44px;
    height: 44px;
    left: -20px;
}

.life-event-list-icon img {
    width: 22px;
    max-width: 22px;
    max-height: 22px;
    left: 10px;
    top: 10px;
}

.life-event-list-actions {
    right: 0;
    color: #909090;
    top: 18px;
}

.life-event-list-content {
    padding-left: 40px;
    padding-right: 70px;
}
</style>

<template>
    <div class="">
        <div class="col-xs-12">
          <h3>
            Life events

            <span class="relative" style="top: -7px;">
              <a class="btn edit-information" @click="showAdd = true">Add life event</a>
            </span>
          </h3>
        </div>

        <!-- CREATE LIFE EVENT BOX -->
        <create-life-event :hash="hash"
                            v-if="showAdd == true"
                            v-on:updateLifeEventTimeline="getLifeEvents"
                            v-on:dismissModal="showAdd = false"
                            >
        </create-life-event>

        <!-- LISTING OF LIFE EVENTS -->
        <div class="bt b--gray-monica" style="margin-left: 20px;">
            <div v-for="lifeEvent in lifeEvents">
                <div class="bl bb b--gray-monica relative pa3 life-event-list-content">
                    <div class="absolute life-event-list-icon">
                        <img class="relative" :src="'/img/people/life-events/types/' + lifeEvent.default_life_event_type_key + '.svg'">
                    </div>
                    <div class="absolute life-event-list-actions f7">
                        <span>{{ lifeEvent.happened_at }}</span>
                        <span></span>
                    </div>
                    <p><span class="b">{{ lifeEvent.life_event_type }}</span> {{ lifeEvent.name }}</p>
                    <p>{{ lifeEvent.note }}</p>
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
                lifeEvents: [],
                showAdd: false,
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
                            this.lifeEvents = response.data
                            this.showAdd = false
                        });
            },
        }
    }
</script>
