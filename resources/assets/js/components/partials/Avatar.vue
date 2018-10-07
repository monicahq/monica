<style scoped>
</style>

<template>
  <div>
    <template v-if="clickable == true">
      <a :href="'/people/' + contact.id">
        <img v-if="contact.has_avatar" :src="contact.avatar_url" class="br4 h3 w3 dib tc" width="43" v-tooltip.bottom="contact.complete_name">
        <img v-else-if="contact.gravatar_url" :src="contact.gravatar_url" class="br4 h3 w3 dib tc" width="43" v-tooltip.bottom="contact.complete_name">
        <div v-else v-tooltip.bottom="contact.complete_name" :style="{ 'background-color': contact.default_avatar_color }" class="br4 h3 w3 dib pt3 white tc f4">
          {{ contact.initials }}
        </div>
      </a>
    </template>
    <template v-else>
      <img v-if="contact.has_avatar" :src="contact.avatar_url" class="br4 h3 w3 dib tc" width="43" v-tooltip.bottom="contact.complete_name">
      <img v-else-if="contact.gravatar_url" :src="contact.gravatar_url" class="br4 h3 w3 dib tc" width="43" v-tooltip.bottom="contact.complete_name">
      <div v-else v-tooltip.bottom="contact.complete_name" :style="{ 'background-color': contact.default_avatar_color }" class="br4 h3 w3 dib pt3 white tc f4">
        {{ contact.initials }}
      </div>
    </template>
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

        props: [
          'contact',
          'clickable'
        ],

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
