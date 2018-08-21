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
                    :participant-name="participantName"
                    v-on:contentChange="updateContent($event, message)"
                    v-on:deleteMessage="deleteMessage($event, message)"
                    :display-trash="displayTrash">
                </message>
            </div>
            <p class="tc"><a @click="addMessage" class="btn btn-secondary pointer">Add another message</a></p>
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
                uid: 1,
                displayTrash: false,
            };
        },

        props: {
            participantName: {
                type: String,
            },
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
                    content: '',
                    author: 'me'
                })
            },

            addMessage() {
                this.messages.push({
                    uid: this.uid++,
                    content: '',
                    author: 'me'
                })
                if (this.messages.length > 1) {
                    this.displayTrash = true
                }
            },

            updateContent(updatedContent, message) {
              message.content = updatedContent
            },

            deleteMessage(uid, message) {
              this.messages.splice(this.messages.indexOf(uid), 1)
              if (this.messages.length <= 1) {
                this.displayTrash = false
              }
            },
        }
    }
</script>
