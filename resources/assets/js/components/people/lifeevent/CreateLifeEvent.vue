<style scoped>
</style>

<template>
    <section class="ph3 ph0-ns life-event">

        <notifications group="main" position="top middle" width="400" />

        <div class="mt4 mw7 center mb3">

            <!-- Breadcrumb -->
            <ul v-if="view == 'types' || view == 'add'" class="ba b--gray-monica pa2 mb2">
                <li class="di"><a class="pointer" @click="view = 'categories'">{{ $t('people.life_event_create_category') }}</a></li>
                <li class="di" v-if="view == 'types'">> {{ this.activeCategory.name }}</li>
                <li class="di" v-if="view == 'add'">> <a class="pointer" @click="view = 'types'">{{ this.activeCategory.name }}</a></li>
                <li class="di" v-if="view == 'add'">> {{ $t('people.life_event_create_life_event') }}</li>
            </ul>

            <!-- List of events -->
            <ul class="ba b--gray-monica br2" v-if="view != 'add'">

                <!-- CATEGORIES -->
                <li class="relative pointer bb b--gray-monica b--gray-monica pa2 life-event-add-row" v-for="category in categories" @click="getType(category)" v-if="view == 'categories'" v-bind:key="category.id">
                    <div class="dib mr2">
                        <img :src="'/img/people/life-events/categories/' + category.default_life_event_category_key + '.svg'" style="min-width: 12px;">
                    </div>
                    {{ category.name }}

                    <svg class="absolute life-event-add-arrow" width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M8.75071 5.66783C9.34483 6.06361 9.34483 6.93653 8.75072 7.33231L1.80442 11.9598C1.13984 12.4025 0.25 11.9261 0.25 11.1275L0.25 1.87263C0.25 1.07409 1.13984 0.59767 1.80442 1.04039L8.75071 5.66783Z" fill="#C4C4C4"/>
                    </svg>
                </li>

                <!-- TYPES -->
                <li class="relative pointer bb b--gray-monica b--gray-monica pa2 life-event-add-row" v-for="type in types" @click="displayAddScreen(type)" v-if="view == 'types'" v-bind:key="type.id">
                    <div class="dib mr2">
                        <img :src="'/img/people/life-events/types/' + type.default_life_event_type_key + '.svg'" style="min-width: 12px;">
                    </div>
                    {{ type.name }}

                    <svg class="absolute life-event-add-arrow" width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M8.75071 5.66783C9.34483 6.06361 9.34483 6.93653 8.75072 7.33231L1.80442 11.9598C1.13984 12.4025 0.25 11.9261 0.25 11.1275L0.25 1.87263C0.25 1.07409 1.13984 0.59767 1.80442 1.04039L8.75071 5.66783Z" fill="#C4C4C4"/>
                    </svg>
                </li>
            </ul>

            <!-- ADD SCREEN -->
            <div class="ba b--gray-monica br2 pt4" v-if="view == 'add'">
                <div class="life-event-add-icon tc center">
                    <img :src="'/img/people/life-events/types/' + activeType.default_life_event_type_key + '.svg'" style="min-width: 17px;">
                </div>

                <h3 class="pt3 ph4 f3 fw5 tc">
                    {{ $t('people.life_event_sentence_' + this.activeType.default_life_event_type_key) }}
                </h3>

                <!-- This field will be the same for every life event type no matter what, as the date is the only required field -->
                <div class="ph4 pv3 mb3 mb0-ns bb b--gray-monica">
                    <label for="another" class="mr2">{{ $t('people.life_event_date_it_happened') }}</label>
                    <div class="flex mb3">
                        <div class="mr2">
                            <form-select
                                v-model="selectedYear"
                                :options="years"
                                :id="'year'"
                                @input="updateDate"
                                :title="''" :class="[ dirltr ? 'mr2' : '' ]">
                            </form-select>
                        </div>
                        <div class="mr2">
                            <form-select
                                v-model="selectedMonth"
                                :options="months"
                                :id="'month'"
                                @input="updateDate"
                                :title="''" :class="[ dirltr ? 'mr2' : '' ]">
                            </form-select>
                        </div>
                        <div>
                            <form-select
                                v-model="selectedDay"
                                :options="days"
                                :id="'day'"
                                @input="updateDate"
                                :title="''" :class="[ dirltr ? '' : 'mr2' ]">
                            </form-select>
                        </div>
                    </div>
                    <p class="f6">{{ $t('people.life_event_create_date') }}</p>
                </div>

                <create-default-life-event v-on:contentChange="updateLifeEventContent($event)"></create-default-life-event>

                <!-- YEARLY REMINDER -->
                <div class="ph4 pv3 mb3 mb0-ns bb b--gray-monica">
                    <label :class="[dirltr ? 'mr3 mb0 form-check-label pointer' : 'ml3 mb0 form-check-label pointer']">
                        <input class="form-check-input" id="addReminder" name="addReminder" type="checkbox" v-model="newLifeEvent.has_reminder">
                        {{ $t('people.life_event_create_add_yearly_reminder') }}
                    </label>
                </div>

                <!-- FORM ACTIONS -->
                <div class="ph4-ns ph3 pv3 bb b--gray-monica">
                    <div class="flex-ns justify-between">
                        <div>
                            <a @click="$emit('dismissModal')" class="btn btn-secondary tc w-auto-ns w-100 mb2 pb0-ns">{{ $t('app.cancel') }}</a>
                        </div>
                        <div>
                            <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" @click="store()">{{ $t('app.add') }}</button>
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
                selectedDay: 0,
                selectedMonth: 0,
                selectedYear: 0,
                newLifeEvent: {
                    name: '',
                    note: '',
                    happened_at: '',
                    life_event_type_id: 0,
                    happened_at_month_unknown: false,
                    happened_at_day_unknown: false,
                    specific_information: '',
                    has_reminder: false,
                },
                categories: [],
                activeCategory: '',
                activeType: '',
                types: [],
                view: 'categories',
                dirltr: true,
            };
        },

        props: ['hash', 'years', 'months', 'days'],

        mounted() {
            this.prepareComponent()
        },

        methods: {
            prepareComponent() {
                this.getCategories()
                this.dirltr = this.$root.htmldir == 'ltr'
                this.newLifeEvent.happened_at = moment().format('YYYY-MM-DD')
                this.selectedYear = moment().year()
                this.selectedMonth = moment().month() + 1 // month is zero indexed (O_o) in moments.js
                this.selectedDay = moment().date()
            },

            displayAddScreen(type) {
                this.view = 'add'
                this.activeType = type
                this.newLifeEvent.life_event_type_id = type.id
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

            updateLifeEventContent(lifeEvent) {
                this.newLifeEvent.note = lifeEvent.note
                this.newLifeEvent.name = lifeEvent.name
                this.newLifeEvent.specific_information = lifeEvent.specific_information
            },

            /**
             * Sets the date when the user chooses either an empty month
             * or an empty day of the month.
             * If the user chooses an empty day, the day is set to 1 and we use
             * a boolean to indicate that the day is unknown.
             * Same for the month.
             */
            updateDate() {
                this.newLifeEvent.happened_at = this.selectedYear + '-' + this.selectedMonth + '-' + this.selectedDay
                this.newLifeEvent.happened_at_month_unknown = false
                this.newLifeEvent.happened_at_day_unknown = false

                if (this.selectedDay == 0) {
                    this.newLifeEvent.happened_at_day_unknown = true
                    this.newLifeEvent.happened_at = this.selectedYear + '-' + this.selectedMonth + '-01'
                }

                if (this.selectedMonth == 0) {
                    this.newLifeEvent.happened_at_month_unknown = true
                    this.newLifeEvent.happened_at_day_unknown = true
                    this.newLifeEvent.happened_at = this.selectedYear + '-01-01'
                    this.selectedDay = 0
                }
            },

            store() {
                axios.post('/people/' + this.hash + '/lifeevents', this.newLifeEvent)
                        .then(response => {
                            this.$emit('updateLifeEventTimeline', response.data)

                            this.$notify({
                                group: 'main',
                                title: this.$t('people.life_event_create_success'),
                                text: '',
                                type: 'success'
                            });
                      });
            },
        }
    }
</script>
