<style scoped>
</style>

<template>
  <div class="bg-white pa3">
    <span class="di mr3">Who wrote this message?</span>
    <div class="di mr3">
      <input type="radio" id="me" :name="'who_wrote_' + uid" value="me" :checked="author == 'me'">
      <label for="me" class="pointer">You</label>
    </div>
    <div class="di">
      <input type="radio" id="other" :name="'who_wrote_' + uid" value="other" :checked="author == 'other'">
      <label for="other" class="pointer">{{ participantName }}</label>
    </div>

    <form-textarea
      v-bind:required="true"
      v-bind:noLabel="true"
      v-bind:rows="4"
      v-bind:content="content"
      v-bind:placeholder="'Write down what was said'"
      v-bind:id="'content_' + uid">
    </form-textarea>
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
            };
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        props: {
            uid: {
                type: Number,
            },
            id: {
                type: String,
            },
            participantName: {
                type: String,
            },
            author: {
              type: String,
            },
            placeholder: {
                type: String,
            },
            required: {
                type: Boolean,
            },
            noLabel: {
                type: Boolean,
            },
            width: {
              type: Number,
            },
            rows: {
              type: Number,
            },
            content: {
              type: String,
            }
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            },
        }
    }
</script>
