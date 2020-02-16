<style lang="scss" scoped>
.flash.notification {
    z-index: 99999999999;
    position: fixed;
    bottom: 30px;
    right: 30px;
    opacity: 0;
    transform: translate(100%);
    transition: all 0.8s ease-in-out;
    &.is-visible {
        transform: translate(0);
        opacity: 1;
    }
}
</style>

<template>
  <div class="flash notification" :class="[
      levelClass, isOpen ? isVisibleClass : ''
  ]">
      <button class="delete" @click="isOpen = false"></button>
      {{ messageText }}
  </div>
</template>

<script>

export default {

  props: ['level', 'message'],

  data() {
    return {
      isOpen: false,
      isVisibleClass: 'is-visible',
      closeAfter: 10000,// 10 seconds, you can change that
      levelClass: null,
      messageText: null
    }
  },

  created() {
    if (this.level) {
      this.levelClass = 'is-' + this.level;
    }

    if (this.message) {
      this.messageText = this.message;
      this.flash();
    }

    let self = this;

    window.events.$on(
      'flash', data => self.flash(data)
    );
  },

  methods: {
    flash(data) {
      if (data) {
        this.messageText = data.message;
        this.levelClass = 'is-' + data.level;
      }

      let self = this;

      setTimeout(() => {
        self.isOpen = true;
      }, 100);

      this.hide();
    },

    hide() {
      let self = this;

      setTimeout(() => {
        self.isOpen = false;
      }, self.closeAfter);
    }
  },
}
</script>
