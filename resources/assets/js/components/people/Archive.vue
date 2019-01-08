<style scoped>
.fa {
    top: 1px;
    color: #b1b1b1;
}
</style>

<template>
  <div>
    <notifications group="archive" position="bottom right" :duration="5000" width="400" />

    <a class="pointer" :title="$t('people.contact_archive_help')" @click="toggle">
      {{ isActive ? $t('people.contact_archive') : $t('people.contact_unarchive') }}
    </a>

    <span v-tooltip.top="$t('people.contact_archive_help')">
      <i class="fa fa-info-circle relative pointer"></i>
    </span>
  </div>
</template>

<script>
export default {

  props: {
    hash: {
      type: String,
      default: '',
    },
    active: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      isActive: false,
    };
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.isActive = this.active;
    },

    toggle() {
      axios.put('/people/' + this.hash + '/archive')
        .then(response => {
          this.isActive = response.data.is_active;

          this.$notify({
            group: 'archive',
            title: this.$t('app.default_save_success'),
            text: '',
            type: 'success'
          });
        });
    },
  }
};
</script>
