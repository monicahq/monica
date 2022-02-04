<style lang="scss" scoped>
.flash.notification {
  z-index: 99999999999;
  bottom: 30px;
  right: 0px;
  transform: translate(100%);
  transition: all 0.8s ease-in-out;
  background-color: #fff;
  border: 1px solid #e7e7e7;
  border-radius: 6px;
  box-shadow: 1px 1px 2px rgba(122, 122, 122, 0.17);
  padding: 10px 20px;

  &.is-visible {
    transform: translate(0);
    opacity: 1;
    right: 30px;
  }
}
</style>

<template>
  <div class="flash notification fixed" :class="[levelClass, isOpen ? isVisibleClass : '']">
    <span class="mr-1"> 👋 </span> {{ messageText }}
  </div>
</template>

<script>
export default {
  props: {
    level: {
      type: String,
      default: '',
    },
    message: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      isOpen: false,
      isVisibleClass: 'is-visible',
      closeAfter: 5000, // 10 seconds, you can change that
      levelClass: null,
      messageText: null,
    };
  },

  created() {
    if (this.level) {
      this.levelClass = 'is-' + this.level;
    }

    if (this.message) {
      this.messageText = this.message;
      this.show();
    }

    const self = this;

    this.$on('flash', (data) => self.show(data));
  },

  methods: {
    show(data) {
      if (data) {
        this.messageText = data.message;
        this.levelClass = 'is-' + data.level;
      }

      this.act(true, 100);
      this.hide();
    },

    hide() {
      this.act(false, this.closeAfter);
    },

    act(action, timeout) {
      const self = this;

      setTimeout(() => {
        self.isOpen = action;
      }, timeout);
    },
  },
};
</script>
