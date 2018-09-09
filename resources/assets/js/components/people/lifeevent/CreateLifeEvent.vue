<style scoped>
</style>

<template>
    <section class="ph3 ph0-ns life-event">
        <div class="mt4 mw7 center mb3">

            <!-- Breadcrumb -->
            <ul v-if="view == 'types' || view == 'add'" class="ba b--gray-monica pa2 mb2">
                <li class="di"><a class="pointer" @click="view = 'categories'">All categories</a></li>
                <li class="di" v-if="view == 'types'">> {{ this.activeCategory.name }}</li>
                <li class="di" v-if="view == 'add'">> <a class="pointer" @click="view = 'types'">{{ this.activeCategory.name }}</a></li>
                <li class="di" v-if="view == 'add'">> Add life event</li>
            </ul>

            <!-- List of events -->
            <ul class="ba b--gray-monica br2" v-if="view != 'add'">

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
                <h3 class="pt3 ph4 f3 fw5">
                    <img :src="'/img/people/life-events/types/' + activeType.default_life_event_type_key + '.svg'">
                    {{ this.activeType.name }}
                </h3>

                <create-default-life-event-type
                            v-bind:months="months"
                            v-bind:days="days"
                            v-bind:years="years">
                </create-default-life-event-type>

                <div class="ph4-ns ph3 pv3 bb b--gray-monica">
                    <div class="flex-ns justify-between">
                        <div>
                            <a @click="$emit('dismissModal')" class="btn btn-secondary w-auto-ns w-100 mb2 pb0-ns">Cancel</a>
                        </div>
                        <div>
                            <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" @click="store()">Add</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
    import moment from 'moment';

    export default {
        data() {
            return {
                newLifeEvent: {
                    name: '',
                    note: '',
                    happened_at: '',
                    life_event_type_id: 0
                },
                categories: [],
                activeCategory: '',
                activeType: '',
                types: [],
                view: 'categories',
            };
        },

        props: ['hash', 'years', 'months', 'days'],

        mounted() {
            this.prepareComponent()
        },

        methods: {
            prepareComponent() {
                this.getCategories()
                this.newLifeEvent.happened_at = moment().format('YYYY-MM-DD')
            },

            getCategories() {
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
                this.activeCategory = category
            },

            broadcastContentChange(note) {
                this.newLifeEvent.note = note
            },

            updateDate(date) {
                this.newLifeEvent.happened_at = date
            },

            displayAddScreen(type) {
                this.view = 'add'
                this.activeType = type
                this.newLifeEvent.life_event_type_id = type.id
            },

            store() {
                axios.post('/people/' + this.hash + '/lifeevents', this.newLifeEvent)
                        .then(response => {
                            this.activeCategory = ''
                            this.activeType = ''
                            this.$emit('updateLifeEventTimeline', this.newLifeEvent)
                      });
            },
        }
    }
</script>
