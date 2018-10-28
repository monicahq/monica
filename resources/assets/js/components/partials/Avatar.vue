<style scoped>
</style>

<template>
  <div>
    <div v-if="clickable == true">
      <div class="tc" v-if="contact.has_avatar">
        <img v-if="contact.has_avatar" :src="contact.avatar_url" class="br4 h3 w3 dib" v-tooltip.bottom="contact.complete_name" @click="goToContact()">
      </div>

      <div class="tc" v-if="contact.avatar_url">
        <img :src="contact.avatar_url" class="br4 h3 w3 dib" width="43" v-tooltip.bottom="contact.complete_name" @click="goToContact()">
      </div>

      <div v-if="!contact.has_avatar" v-tooltip.bottom="contact.complete_name" :style="{ 'background-color': contact.default_avatar_color }" class="br4 h3 w3 dib pt3 white tc f4"  @click="goToContact()">
        {{ contact.initials }}
      </div>
    </div>

    <div v-if="clickable == false">
      <div class="tc" v-if="contact.has_avatar">
        <img v-if="contact.has_avatar" :src="contact.avatar_url" class="br4 h3 w3 dib" v-tooltip="contact.complete_name">
      </div>

      <div class="tc" v-if="contact.avatar_url">
        <img :src="contact.gravatar_url" class="br4 h3 w3 dib" width="43" v-tooltip="contact.complete_name">
      </div>

      <div v-if="!contact.has_avatar" v-tooltip.bottom="contact.complete_name" :style="{ 'background-color': contact.default_avatar_color }" class="br4 h3 w3 dib pt3 white tc f4">
        {{ contact.initials }}
      </div>
    </div>
  </div>
</template>

<script>
    export default {
        props: [
          'contact',
          'clickable'
        ],
    }
</script>
