<style lang="scss" scoped>
</style>

<template>
  <div>
    <p>sdfs</p>

    <slot></slot>
  </div>
</template>

<script>

export default {
  props: {
    title: {
      type: String,
      default: '',
    },
    noMenu: {
      type: Boolean,
      default: false,
    },
    notifications: {
      type: Array,
      default: null,
    },
  },

  data() {
    return {
      loadingState: '',
      modalFind: false,
      showModalNotifications: true,
      dataReturnedFromSearch: false,
      form: {
        searchTerm: null,
        errors: {
          type: Array,
          default: null,
        },
      },
      employees: [],
      teams: [],
    };
  },

  watch: {
    title(title) {
      this.updatePageTitle(title);
    }
  },

  mounted() {
    this.updatePageTitle(this.title);
  },


  methods: {
    updatePageTitle(title) {
      document.title = title ? `${title} | OfficeLife` : 'OfficeLife';
    },

    showFindModal() {
      this.dataReturnedFromSearch = false;
      this.form.searchTerm = null;
      this.employees = [];
      this.teams = [];
      this.modalFind = !this.modalFind;

      this.$nextTick(() => {
        this.$refs.search.focus();
      });
    },

    submit() {
      axios.post('/search/employees', this.form)
        .then(response => {
          this.dataReturnedFromSearch = true;
          this.employees = response.data.data;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = _.flatten(_.toArray(error.response.data));
        });

      axios.post('/search/teams', this.form)
        .then(response => {
          this.dataReturnedFromSearch = true;
          this.teams = response.data.data;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = _.flatten(_.toArray(error.response.data));
        });
    }
  },
};
</script>
