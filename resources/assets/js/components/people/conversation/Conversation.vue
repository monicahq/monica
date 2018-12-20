<style scoped>
  .me {
    width: 0;
    height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-left: 10px solid #fff;
    top: 120px;
    right: -10px;
  }

  .other {
    width: 0;
    height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-right:10px solid #fff;
    top: 120px;
    left: -10px;
  }

  .conversation-block {
    background-color: #D8E2E7;
  }
</style>

<template>
  <div class="pa4-ns ph3 pv2 mb3 mb0-ns bb b--gray-monica">
    <p class="mb2 b">
      {{ $t('people.conversation_add_what_was_said') }}
    </p>
    <div class="pa3 ba b--gray-monica br3 conversation-block">
      <div v-for="message in messages" :key="message.uid" class="relative">
        <div :class="message.author + ' absolute'"></div>
        <message
          :class="{ 'mb3 ml5': message.author == 'me', 'mb3 mr5': message.author == 'other' }"
          :author="message.author"
          :content="message.content"
          :uid="message.uid"
          :participant-name="participantName"
          :display-trash="displayTrash"
          @updateAuthor="updateAuthor($event, message)"
          @contentChange="updateContent($event, message)"
          @deleteMessage="deleteMessage($event, message)"
        />
      </div>
      <p class="tc mb0">
        <a class="btn btn-secondary pointer" @click="addMessage">
          {{ $t('people.conversation_add_another') }}
        </a>
      </p>
      <input type="hidden" name="messages" :value="messages.map(a => a.uid)" />
    </div>
  </div>
</template>

<script>
export default {

    props: {
        participantName: {
            type: String,
            default: '',
        },
        existingMessages: {
            type: Array,
            default: function () {
                return [];
            }
        },
    },

    data() {
        return {
            messages: [],
            uid: 1,
            displayTrash: false,
        };
    },

    mounted() {
        this.prepareComponent();
    },

    methods: {
        prepareComponent() {
            if (this.existingMessages) {
                this.messages = this.existingMessages;
                this.uid = this.messages[this.messages.length - 1].uid + 1;
            } else {
                this.messages.push({
                    uid: this.uid++,
                    content: '',
                    author: 'me'
                });
            }
        },

        addMessage() {
            this.messages.push({
                uid: this.uid++,
                content: '',
                author: 'me'
            });
            if (this.messages.length > 1) {
                this.displayTrash = true;
            }
        },

        updateContent(updatedContent, message) {
            message.content = updatedContent;
        },

        updateAuthor(updatedAuthor, message) {
            message.author = updatedAuthor;
        },

        deleteMessage(uid, message) {
            this.messages.splice(this.messages.indexOf(this.messages.find(item => item.uid === uid)), 1);
            if (this.messages.length <= 1) {
                this.displayTrash = false;
            }
        },
    }
};
</script>
