<style scoped>
  .delete-message-l {
    top: 9px;
    right: 16px;
  }

  .delete-message-r {
    top: 9px;
    left: 16px;
  }

  .btn-secondary {
    font-size: 12px;
    padding: 4px 10px;
  }
</style>

<template>
  <div class="bg-white pa3 relative br3">
    <!-- TRASH CAN -->
    <span v-if="displayTrash" :class="[dirltr ? 'absolute delete-message-l' : 'absolute delete-message-r']">
      <a class="pointer btn btn-secondary" href="" @click.prevent="deleteMessage">
        <svg class="mr1 relative" style="top: 1px;" width="9" height="11" viewBox="0 0 9 11"
             fill="none" xmlns="http://www.w3.org/2000/svg"
        >
          <path fill-rule="evenodd" clip-rule="evenodd" d="M7.27273 0.714286H5.81818C5.81818 0.321429 5.49091 0 5.09091 0H2.90909C2.50909 0 2.18182 0.321429 2.18182 0.714286H0.727273C0.327273 0.714286 0 1.03571 0 1.42857V2.14286C0 2.53571 0.327273 2.85714 0.727273 2.85714V9.28571C0.727273 9.67857 1.05455 10 1.45455 10H6.54545C6.94545 10 7.27273 9.67857 7.27273 9.28571V2.85714C7.67273 2.85714 8 2.53571 8 2.14286V1.42857C8 1.03571 7.67273 0.714286 7.27273 0.714286ZM6.54545 9.28571H1.45455V2.85714H2.18182V8.57143H2.90909V2.85714H3.63636V8.57143H4.36364V2.85714H5.09091V8.57143H5.81818V2.85714H6.54545V9.28571ZM7.27273 2.14286H0.727273V1.42857H7.27273V2.14286Z" fill="#626262" />
        </svg>
        {{ $t('app.delete') }}
      </a>
    </span>

    <!-- AUTHOR -->
    <div class="mb3">
      <span class="di mr3">
        {{ $t('people.conversation_add_who_wrote') }}
      </span>
      <div class="di mr3">
        <input :id="'other_' + uid" v-model="updatedAuthor" class="pointer" type="radio" :name="'who_wrote_' + uid"
               value="other" :checked="updatedAuthor === 'other'" @click="updateAuthor('other')"
        />
        <label :for="'other_' + uid" class="pointer">
          {{ participantName }}
        </label>
      </div>
      <div class="di">
        <input :id="'me_' + uid" v-model="updatedAuthor" class="pointer" type="radio" :name="'who_wrote_' + uid"
               value="me" :checked="updatedAuthor === 'me'" @click="updateAuthor('me')"
        />
        <label :for="'me_' + uid" class="pointer">
          {{ $t('people.conversation_add_you') }}
        </label>
      </div>
    </div>

    <!-- ACTUAL COMMENT -->
    <form-textarea
      :id="'content_' + uid"
      v-model="buffer"
      :required="true"
      :no-label="true"
      :rows="4"
      :placeholder="$t('people.conversation_add_content')"
    />
  </div>
</template>

<script>
export default {

  props: {
    value: {
      type: String,
      default: '',
    },
    uid: {
      type: Number,
      default: 0,
    },
    id: {
      type: String,
      default: '',
    },
    participantName: {
      type: String,
      default: '',
    },
    author: {
      type: String,
      default: '',
    },
    placeholder: {
      type: String,
      default: '',
    },
    required: {
      type: Boolean,
      default: true,
    },
    displayTrash: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      buffer: '',
      updatedAuthor: this.author,
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  watch: {
    value(newValue) {
      this.buffer = newValue;
    }
  },

  mounted() {
    this.buffer = this.value;
  },

  methods: {
    deleteMessage() {
      this.$emit('deleteMessage', this.uid);
    },

    updateAuthor(newAuthor) {
      this.updatedOther = newAuthor;
      this.$emit('updateAuthor', newAuthor);
    },
  }
};
</script>
