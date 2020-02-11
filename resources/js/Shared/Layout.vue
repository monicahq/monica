<style lang="scss" scoped>
.notification {
  bottom: 20px;
  right: 20px;
  width: 450px;
}
</style>

<template>
  <div class="relative">
    <header class="w-100 ph3 ph5-ns pv2 bg-white bb">
      <div class="db dt-ns mw9 center w-100">
        <div class="db dtc-ns v-mid tl w-10">
          <a href="/" class="dib f5 f4-ns fw6 mt0 mb1 link black-70" title="Home">
            Monica
          </a>
        </div>
        <nav class="db dtc-ns v-mid w-100 tl tr-ns mt2 mt0-ns">
          <inertia-link title="Documentation" href="/" class="f6 fw6 hover-blue link black-70 mr2 mr3-m mr4-l dib">
            Dashboard
          </inertia-link>
          <inertia-link title="Components" href="/people" class="f6 fw6 hover-blue link black-70 mr2 mr3-m mr4-l dib">
            Contacts
          </inertia-link>
          <inertia-link title="Gallery of sites built with Tachyons" href="/gallery/" class="f6 fw6 hover-blue link black-70 mr2 mr3-m mr4-l dib">
            Groups
          </inertia-link>
          <inertia-link title="Gallery of sites built with Tachyons" href="/gallery/" class="f6 fw6 hover-blue link black-70 mr2 mr3-m mr4-l dib">
            Events
          </inertia-link>
          <inertia-link title="Gallery of sites built with Tachyons" href="/gallery/" class="f6 fw6 hover-blue link black-70 mr2 mr3-m mr4-l dib">
            Journal
          </inertia-link>
          <inertia-link title="Resources" href="/resources/" class="f6 fw6 hover-blue link black-70 mr2 mr3-m mr4-l dib">
            Settings
          </inertia-link>
        </nav>
      </div>
    </header>

    <slot></slot>

    <template v-if="notification.show">
      <div class="absolute notification ba z-999" v-if="notification.type == 'success'">
        success
      </div>
    </template>
  </div>
</template>

<script>

export default {
  props: {
    title: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      notification: {
        show: false,
        type: null,
        message: null,
      },
    };
  },

  watch: {
    title(title) {
      this.updatePageTitle(title);
    },
  },

  mounted() {
    this.updatePageTitle(this.title);

    if (localStorage.success) {
      this.notification.message = localStorage.success;
      this.notification.type = 'success';
      this.notification.show = true;
      localStorage.removeItem(success);
    }
  },

  methods: {
    updatePageTitle(title) {
      document.title = title ? `${title} | Monica` : 'Monica';
    },

    submit() {
    }
  },
};
</script>
