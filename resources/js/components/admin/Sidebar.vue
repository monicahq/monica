<template>
  <div class="row">
    <div class="col-12 col-sm-3 sidebar-menu">
      <ul class="mb4">
        <li v-for="link in links" :key="link.title" :class="[current(link) ? 'selected' : 'bg-white']">
          <i :class="link.class" aria-hidden="true"></i>
          <strong v-if="current(link)">{{ link.title }}</strong>
          <router-link v-else :to="{ name: link.name }" replace>
            {{ link.title }}
          </router-link>
        </li>
      </ul>
    </div>

    <div class="col-12 col-md-9">
      <div class="br3 ba b--gray-monica bg-white mb4">
        <div class="pa3 bb b--gray-monica">
          <slot></slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {

  computed: {
    links() {
      return [
        {
          title: this.$t('settings.admin_users_title'),
          name: 'users',
          class: 'fa fa-user-circle-o'
        },
        {
          title: this.$t('settings.admin_config'),
          name: 'config',
          class: 'fa fa-cog'
        }
      ];
    },
  },

  mounted() {
  },

  methods: {
    current(link) {
      return this.$route.name == link.name;
    },
  }
};
</script>
