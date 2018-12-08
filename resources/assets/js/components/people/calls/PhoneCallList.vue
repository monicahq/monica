<style scoped>
</style>

<template>
        <div>
            <div class="">
                <h3>
                    ☎️ {{ $t('people.call_title') }}

                    <span class="fr relative" style="top: -7px;">
                        <a @click="displayLogCall = true" class="btn edit-information" v-if="displayLogCall == false">{{ $t('people.modal_call_title') }}</a>
                        <a @click="displayLogCall = false" class="btn edit-information" v-if="displayLogCall">{{ $t('app.cancel') }}</a>
                    </span>
                </h3>
            </div>

            <!-- LOG A CALL -->
            <transition name="fade">
                <div class="ba br3 document-upload-zone mb3 pa3" v-if="displayLogCall">
                    <div class="">
                        <label>{{ $t('people.conversation_add_you') }}</label>
                        <form-textarea
                            v-model="newCall.content"
                            :required="true"
                            :noLabel="true"
                            :rows="4"
                            :placeholder="$t('people.conversation_add_content')"
                            @contentChange="updateContent($event)">
                        </form-textarea>
                        <p class="f6">Want to format your text in a nice way? We support Markdown to add bold, italic, lists and more. Read documentation</p>
                    </div>
                    <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
                        <p class="mb2 b">When did you make this call?</p>
                        <div class="di mr3">
                            <div class="dib">
                                <form-date
                                    v-model="newCall.called_at"
                                    :default-date="todayDate"
                                    @selected="updateDate($event)"
                                    :locale="'en'">
                                </form-date>
                            </div>
                        </div>
                    </div>

                    <!-- ACTIONS -->
                    <div class="ph4-ns ph3 pv3 bb b--gray-monica">
                        <div class="flex-ns justify-between">
                        <div class="">
                            <a @click.prevent="displayLogCall = false; resetFields()" class="btn btn-secondary tc w-auto-ns w-100 mb2 pb0-ns">{{ $t('app.cancel') }}</a>
                        </div>
                        <div class="">
                            <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" @click.prevent="store()">{{ $t('app.add') }}</button>
                        </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- LIST OF CALLS -->
            <div class="ba br2 b--black-10 br--top w-100 mb2" v-for="call in calls" v-bind:key="call.id">
                <div class="pa2">
                    <span v-if="!call.content">{{ $t('people.call_blank_desc', { name: call.contact.first_name }) }}</span>
                    <span v-if="call.content">{{ call.content }}</span>
                </div>
                <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
                    <div class="w-50" :class="[ dirltr ? 'fl' : 'fr' ]">
                        {{ call.called_at | moment }}
                    </div>
                    <div :class="[ dirltr ? 'fl tr' : 'fr tl' ]" class="w-50">
                        <a :class="[ dirltr ? 'mr2' : 'ml2' ]" class="pointer " @click.prevent="updateCall(call)">
                            {{ $t('app.update') }}
                        </a>
                        <a class="pointer" @click.prevent="destroyCall(call)">
                            {{ $t('app.delete') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import moment from 'moment'

    export default {
        data() {
            return {
                calls: [],
                dirltr: true,
                displayLogCall: false,
                todayDate: '',
                newCall: {
                    content: '',
                    called_at: '',
                },
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

        filters: {
            moment: function (date) {
                return moment.utc(date).format('LL')
            }
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent(hash) {
                this.dirltr = this.$root.htmldir == 'ltr'
                this.getCalls()
                this.todayDate = moment().format('YYYY-MM-DD')
                this.newCall.called_at = this.todayDate
            },

            resetFields() {
                this.newCall.content = ''
                this.newCall.called_at = moment().format()
            },

            getCalls() {
                 axios.get('/people/' + this.hash + '/calls')
                    .then(response => {
                        this.calls = response.data.data
                    });
            },

            store() {
                axios.post('/people/' + this.hash + '/calls', this.newCall)
                        .then(response => {
                            this.getCalls()

                            this.resetFields()

                            this.displayLogCall = false

                            this.$notify({
                                group: 'main',
                                title: this.$t('people.life_event_create_success'),
                                text: '',
                                type: 'success'
                            });
                        });
            },

            onRowClick(params) {
                window.location.href = params.row.route;
            },

            updateContent(updatedContent) {
              this.newCall.content = updatedContent
            },

            updateDate(updatedContent) {
              this.newCall.called_at = updatedContent
            },
        }
    }
</script>
