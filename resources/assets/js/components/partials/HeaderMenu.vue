<style scoped>
  .absolute {
    border: 1px solid rgba(27,31,35,.15);
    box-shadow: 0 3px 12px rgba(27,31,35,.15);
    top: 36px;
  }

  .absolute:after,
  .absolute:before {
    content: "";
    display: inline-block;
    position: absolute;
  }

  .absolute:after {
    border: 7px solid transparent;
    border-bottom-color: #fff;
    left: auto;
    right: 10px;
    top: -14px;
  }

  .absolute:before {
    border: 8px solid transparent;
    border-bottom-color: rgba(27,31,35,.15);
    left: auto;
    right: 9px;
    top: -16px;
  }
</style>

<template>
  <div>
    <a class="no-color no-underline relative pointer" @click.prevent="menu = !menu">
      {{ $t('app.main_nav_settings') }} <span class="dropdown-caret"></span>
    </a>
    <div class="absolute br2 bg-white z-max tl pv2 ph3" v-if="menu == true">
      <ul class="list ma0 pa0">
        <li class="pv2">
          <a class="no-color no-underline" href="">
            {{ $t('app.main_nav_changelog') }}
          </a>
        </li>
        <li class="pv2">
          <a class="no-color no-underline" href="">
            {{ $t('app.main_nav_settings') }}
          </a>
        </li>
        <li class="pv2">
          <a class="no-color no-underline" href="">
            {{ $t('app.main_nav_signout') }}
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      menu: false,
    };
  },

  created() {
    window.addEventListener('click', this.close);
  },

  beforeDestroy() {
    window.removeEventListener('click', this.close);
  },

  methods: {
    prepareComponent() {
      this.getPrimaryEmotions();
    },

    close(e) {
      if (!this.$el.contains(e.target)) {
        this.menu = false;
      }
    },
  }
};
</script>
