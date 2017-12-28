<style scoped>
</style>

<template>
  <div>
    <div class="tc" v-if="contact.information.avatar.has_avatar">
      <img v-if="contact.information.avatar.has_avatar" :src="attendees.information.avatar.avatar_url" class="br4 h3 w3 dib" v-tooltip="contact.complete_name">
    </div>

    <div class="tc" v-if="contact.information.avatar.gravatar_url">
      <img
        :src="contact.information.avatar.gravatar_url"
        class="br4 h3 w3 dib" width="43">
    </div>

    <div v-if="!contat.information.avatar.has_avatar" v-bind:style="{ 'background-color': contact.information.avatar.default_avatar_color }" class="br4 h3 w3 dib pt3 white">
      {{ contact.initials }}
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

        props: ['contact'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            },
        }
    }
</script>
