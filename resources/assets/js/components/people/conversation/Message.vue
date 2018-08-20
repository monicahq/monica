<style scoped>
</style>

<template>
  <div class="bg-white pa3">
    <span class="di mr3">Who wrote this message?</span>
    <div class="di mr3">
      <input type="radio" :id="'me_' + uid" :name="'who_wrote_' + uid" v-model="author" value="me" :checked="author == 'me'">
      <label :for="'me_' + uid" class="pointer">You</label>
    </div>
    <div class="di">
      <input type="radio" :id="'other_' + uid" :name="'who_wrote_' + uid" v-model="author" value="other" :checked="author == 'other'">
      <label :for="'other_' + uid" class="pointer">{{ participantName }}</label>
    </div>

    <form-textarea
      v-model="buffer"
      v-bind:required="true"
      v-bind:noLabel="true"
      @input="$emit('input', buffer)"
      v-bind:rows="4"
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
                buffer: this.content
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

        // watch: {
        //   buffer (value) {

        //   }
        // },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            },
        }
    }
</script>
