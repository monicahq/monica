<style scoped>
</style>

<template>
    <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
        <div class="pa3 ba b--gray-monica br3 bg-gray-monica">
            <div v-for="message in messages">
                <message class="mb3"
                    :author="message.author"
                    :content="message.content"
                    :uid="message.uid"
                    :participant-name="participantName">
                </message>
            </div>
            <a @click="addMessage()">Add another message</a>
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
                messages: [],
                uid: 1
            };
        },

        props: {
            participantName: {
                type: String,
            }
        },

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
                this.messages.push({
                    uid: this.uid++,
                    content: 'fuck',
                    author: 'me'
                })
            },

            addMessage() {
                this.messages.push({
                    uid: this.uid++,
                    content: '',
                    author: 'me'
                })
            }
        }
    }
</script>
