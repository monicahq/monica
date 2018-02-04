<style scoped>
</style>

<template>
  <div>
    <div v-if="clickable == true">
      <div class="tc" v-if="contact.has_avatar">
        <img v-if="contact.has_avatar" :src="contact.avatar_url" class="br4 h3 w3 dib" v-tooltip="contact.complete_name" v-on:click="goToContact()">
      </div>

      <div class="tc" v-if="contact.gravatar_url">
        <img
          :src="contact.gravatar_url"
          class="br4 h3 w3 dib" width="43" v-on:click="goToContact()">
      </div>

      <div v-if="!contact.has_avatar" v-tooltip.bottom="contact.complete_name" v-bind:style="{ 'background-color': contact.default_avatar_color }" class="br4 h3 w3 dib pt3 white tc f4"  v-on:click="goToContact()">
        {{ contact.initials }}
      </div>
    </div>

    <div v-if="clickable == false">
      <div class="tc" v-if="contact.has_avatar">
        <img v-if="contact.has_avatar" :src="contact.avatar_url" class="br4 h3 w3 dib" v-tooltip="contact.complete_name">
      </div>

      <div class="tc" v-if="contact.gravatar_url">
        <img
          :src="contact.gravatar_url"
          class="br4 h3 w3 dib" width="43">
      </div>

      <div v-if="!contact.has_avatar" v-tooltip.bottom="contact.complete_name" v-bind:style="{ 'background-color': contact.default_avatar_color }" class="br4 h3 w3 dib pt3 white tc f4">
        {{ contact.initials }}
      </div>
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

        props: ['contact', 'clickable'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            },

            goToContact() {
                window.location.href='/people/' + this.contact.id;
            }
        }
    }
</script>
