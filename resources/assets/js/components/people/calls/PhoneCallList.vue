<style scoped>
</style>

<template>
        <div>
            <div class="">
                <h3 class="mb2">
                    ☎️ {{ $t('people.call_title') }}

                    <span class="fr relative" style="top: -7px;">
                        <a @click="displayLogCall = true" class="btn edit-information" v-if="displayLogCall == false">{{ $t('people.modal_call_title') }}</a>
                        <a @click="displayLogCall = false" class="btn edit-information" v-if="displayLogCall">{{ $t('app.cancel') }}</a>
                    </span>
                </h3>
            </div>

            <!-- BLANK STATE -->
            <div class="w-100" v-if="!displayLogCall && calls.length == 0">
                <div class="bg-near-white tc pa3 br2 ba b--light-gray">
                    <p>{{ $t('people.call_blank_title', { name: name }) }}</p>
                    <a class="pointer" @click.prevent="displayLogCall = true">{{ $t('people.modal_call_title') }}</a>
                </div>
            </div>

            <!-- LOG A CALL -->
            <transition name="fade">
                <div class="ba br3 mb3 pa3 b--black-40" v-if="displayLogCall">
                    <div class="dt dt--fixed pb3 mb3 mb0-ns">
                        <!-- WHEN -->
                        <div class="dtc pr2">
                            <p class="mb2">{{ $t('people.modal_call_exact_date') }}</p>
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

                        <!-- WHO CALLED -->
                        <div class="dtc">
                            <p class="mb2">{{ $t('people.modal_call_who_called') }}</p>
                            <div class="di mr3">
                                <input type="radio" class="mr1" id="you" name="contact_called" :value="false" v-model="newCall.contact_called">
                                <label for="you" class="pointer">{{ $t('people.call_you_called') }}</label>
                            </div>
                            <div class="di mr3">
                                <input type="radio" class="mr1" id="contact" name="contact_called" :value="true" v-model="newCall.contact_called">
                                <label for="contact" class="pointer">{{ $t('people.call_he_called', { name : name }) }}</label>
                            </div>
                        </div>
                    </div>

                    <!-- CONTENT -->
                    <div class="bb b--gray-monica">
                        <label>{{ $t('people.modal_call_comment') }}</label>
                        <form-textarea
                            v-model="newCall.content"
                            :required="true"
                            :noLabel="true"
                            :rows="4"
                            :placeholder="$t('people.conversation_add_content')"
                            @contentChange="updateContent($event)">
                        </form-textarea>
                        <p class="f6">{{ $t('app.markdown_description')}} <a href="https://guides.github.com/features/mastering-markdown/" target="_blank">{{ $t('app.markdown_link') }}</a></p>
                    </div>

                    <!-- ACTIONS -->
                    <div class="pt3">
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
                <div class="pa2" v-show="editCallId != call.id">
                    <span v-if="!call.content">{{ $t('people.call_blank_desc', { name: call.contact.first_name }) }}</span>
                    <span v-if="call.content" v-html="compiledMarkdown(call.content)"></span>
                </div>

                <!-- INLINE UPDATE DIV -->
                <div class="pa2" v-show="editCallId == call.id">
                    <div>
                        <div>
                            <label>{{ $t('people.modal_call_comment') }}</label>
                            <textarea
                                v-model="editCall.content"
                                @contentChange="updateEditCallContent($event)"
                                autofocus
                                rows="4"
                                class="br2 f5 w-100 ba b--black-40 pa2 outline-0"></textarea>
                            <p class="f6">Want to format your text in a nice way? We support Markdown to add bold, italic, lists and more. Read documentation</p>
                        </div>

                        <!-- WHO CALLED -->
                        <div class="pb3 mb3 mb0-ns">
                            <p class="mb2">{{ $t('people.modal_call_who_called') }}</p>
                            <div class="di mr3">
                                <input type="radio" class="mr1" :id="'you' + call.id" :name="'contact_called' + call.id" :value="false" v-model="editCall.contact_called">
                                <label :for="'you' +  call.id" class="pointer">{{ $t('people.call_you_called') }}</label>
                            </div>
                            <div class="di mr3">
                                <input type="radio" class="mr1" :id="'contact' + call.id" :name="'contact_called' + call.id" :value="true" v-model="editCall.contact_called">
                                <label :for="'contact' + call.id" class="pointer">{{ $t('people.call_he_called', { name : name }) }}</label>
                            </div>
                        </div>

                        <!-- ACTIONS -->
                        <div class="">
                            <div class="flex-ns justify-between">
                            <div class="">
                                <a @click.prevent="editCallId = 0" class="btn btn-secondary tc w-auto-ns w-100 mb2 pb0-ns">{{ $t('app.cancel') }}</a>
                            </div>
                            <div class="">
                                <button class="btn btn-primary w-auto-ns w-100 mb2 pb0-ns" @click.prevent="update()">{{ $t('app.update') }}</button>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ADDITIONAL INFORMATION -->
                <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
                    <div class="w-50" :class="[ dirltr ? 'fl' : 'fr' ]">
                        <span :class="[ dirltr ? 'mr3' : 'ml3' ]">{{ call.called_at | moment }}</span>
                        <span>{{ call.contact_called ? $t('people.call_he_called', { name : name }) : $t('people.call_you_called') }}</span>
                    </div>

                    <div :class="[ dirltr ? 'fl tr' : 'fr tl' ]" class="w-50">
                        <a :class="[ dirltr ? 'mr2' : 'ml2' ]" class="pointer " @click.prevent="showEditBox(call)">
                            {{ $t('app.update') }}
                        </a>
                        <a class="pointer" @click.prevent="showDestroyCall(call)" v-show="destroyCallId != call.id">
                            {{ $t('app.delete') }}
                        </a>
                        <ul class="di" v-show="destroyCallId == call.id">
                            <li class="di"><a class="pointer mr1" @click.prevent="destroyCallId = 0">{{ $t('app.cancel') }}</a></li>
                            <li class="di"><a class="pointer red" @click.prevent="destroyCall(call)">{{ $t('app.delete_confirm') }}</a></li>
                        </ul>
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
                editCallId: 0,
                destroyCallId: 0,
                newCall: {
                    content: '',
                    called_at: '',
                    contact_called: false,
                },
                editCall: {
                    content: '',
                    contact_called: false,
                }
            };
        },

        props: {
            hash: {
                type: String,
            },
            name: {
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

            compiledMarkdown (text) {
                return marked(text, { sanitize: true })
            },

            resetFields() {
                this.newCall.content = ''
                this.newCall.called_at = this.todayDate
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
                                title: this.$t('people.calls_add_success'),
                                text: '',
                                type: 'success'
                            });
                        });
            },

            update() {
                axios.put('/people/' + this.hash + '/calls/' + this.editCallId, this.editCall)
                        .then(response => {
                            this.getCalls()
                            this.editCallId = 0

                            this.$notify({
                                group: 'main',
                                title: this.$t('app.default_save_success'),
                                text: '',
                                type: 'success'
                            });
                        });
            },

            showEditBox(call) {
                this.editCallId = call.id
                this.editCall.content = call.content
                this.editCall.contact_called = call.contact_called
                this.editCall.called_at = moment.utc(call.called_at).format('YYYY-MM-DD')
            },

            updateContent(updatedContent) {
              this.newCall.content = updatedContent
            },

            updateEditCallContent(updatedContent) {
              this.editCall.content = updatedContent
            },

            updateDate(updatedContent) {
              this.newCall.called_at = updatedContent
            },

            showDestroyCall(call) {
                this.destroyCallId = call.id
            },

            destroyCall(call) {
                axios.delete('/people/' + this.hash + '/calls/' + this.destroyCallId)
                        .then(response => {
                            this.calls.splice(this.calls.indexOf(call), 1)
                        });
            }
        }
    }
</script>
