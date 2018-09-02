<style scoped>
</style>

<template>
    <section class="ph3 ph0-ns life-event">
        <div class="mt4 mw7 center mb3">

            <!-- Breadcrumb -->
            <ul v-if="view == 'types' || view == 'add'" class="ba b--gray-monica pa2 mb2">
                <li class="di"><a class="pointer" @click="view = 'categories'">All categories</a></li>
                <li class="di" v-if="view == 'types'">{{ this.activeCategory }}</li>
                <li class="di" v-if="view == 'add'"><a class="pointer" @click="view = 'types'">{{ this.activeType }}</a></li>
                <li class="di">Add life event</li>
            </ul>

            <ul class="ba b--gray-monica br2">

                <!-- CATEGORIES -->
                <li class="relative pointer bb b--gray-monica b--gray-monica pa2 life-event-add-row" v-for="category in categories" @click="getType(category)" v-if="view == 'categories'">
                    <div class="dib mr2">
                        <img :src="'/img/people/life-events/categories/' + category.default_life_event_category_key + '.svg'">
                    </div>
                    {{ category.name }}

                    <svg class="absolute life-event-add-arrow" width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M8.75071 5.66783C9.34483 6.06361 9.34483 6.93653 8.75072 7.33231L1.80442 11.9598C1.13984 12.4025 0.25 11.9261 0.25 11.1275L0.25 1.87263C0.25 1.07409 1.13984 0.59767 1.80442 1.04039L8.75071 5.66783Z" fill="#C4C4C4"/>
                    </svg>
                </li>

                <!-- TYPES -->
                <li class="relative pointer bb b--gray-monica b--gray-monica pa2 life-event-add-row" v-for="type in types" @click="displayAddScreen(type)" v-if="view == 'types'">
                    <div class="dib mr2">
                        <img :src="'/img/people/life-events/types/' + type.default_life_event_type_key + '.svg'">
                    </div>
                    {{ type.name }}

                    <svg class="absolute life-event-add-arrow" width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M8.75071 5.66783C9.34483 6.06361 9.34483 6.93653 8.75072 7.33231L1.80442 11.9598C1.13984 12.4025 0.25 11.9261 0.25 11.1275L0.25 1.87263C0.25 1.07409 1.13984 0.59767 1.80442 1.04039L8.75071 5.66783Z" fill="#C4C4C4"/>
                    </svg>
                </li>
            </ul>

            <!-- ADD SCREEN -->
            <div class="ba b--gray-monica br2" v-if="view == 'add'">
                <h3>{{ this.activeType }}</h3>

                <svg class="absolute life-event-add-arrow" width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M8.75071 5.66783C9.34483 6.06361 9.34483 6.93653 8.75072 7.33231L1.80442 11.9598C1.13984 12.4025 0.25 11.9261 0.25 11.1275L0.25 1.87263C0.25 1.07409 1.13984 0.59767 1.80442 1.04039L8.75071 5.66783Z" fill="#C4C4C4"/>
                </svg>
            </div>
        </div>
    </section>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                lifeEvents: [],
                categories: [],
                activeCategory: '',
                activeType: '',
                types: [],
                view: 'categories',
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
                axios.get('/lifeevents/categories')
                        .then(response => {
                            this.categories = response.data.data;
                        });
            },

            getType(category) {
                axios.get('/lifeevents/categories/' + category.id + '/types')
                        .then(response => {
                            this.types = response.data.data;
                        });

                this.view = 'types'
                this.activeCategory = category.name
            },

            displayAddScreen(type) {
                this.view = 'add'
                this.activeType = type.name
            }
        }
    }
</script>
