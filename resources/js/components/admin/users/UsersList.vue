<style scoped>
</style>

<template>
  <sidebar>
    <list-screen ref="list" v-slot:default="slotProps" :title="$t('settings.admin_users_title')" resource="users">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :rows="slotProps.entries"
        :total-rows="slotProps.totalRecords"
        :row-style-class="getRowStyleClass"
        style-class="vgt-table condensed striped"
        :rtl="!dirltr"
        :sort-options="{
          enabled: true
        }"
        :pagination-options="{
          enabled: true,
          position: 'bottom',
          perPage: serverParams.perPage,
          page: 1,
          perPageDropdown: perPageDropdown,
          mode: 'pages',
          nextLabel: $t('people.people_search_next'),
          prevLabel: $t('people.people_search_prev'),
          rowsPerPageLabel: $t('people.people_search_rows_per_page'),
          ofLabel: $t('people.people_search_of'),
          pageLabel: $t('people.people_search_page'),
          allLabel: $t('people.people_search_all'),
        }"
        :search-options="{
          enabled: true,
          placeholder: $t('people.people_search'),
        }"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-search="onSearch"
        @on-row-click="onRowClick"
      >
        <template v-slot:emptystate>
          <div class="vgt-center-align vgt-text-disabled h3">
            {{ $t('app.no_entry_found' ) }}
          </div>
        </template>
      </vue-good-table>
    </list-screen>
  </sidebar>
</template>

<script>
import ListScreen from './../ListScreen.vue';
import Sidebar from '../Sidebar.vue';

export default {
  components: {
    ListScreen,
    Sidebar,
  },

  props: {
    wait: {
      type: Number,
      default: 200
    }
  },

  data() {
    return {
      users: [],
      callUserSearch: null,

      totalRecords: 0,

      serverParams: {
        search: '',
        page: 1,
        perPage: 30
      }
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },

    toggleOptions() {
      return {
        checked: this.$t('app.yes'),
        unchecked: this.$t('app.no')
      };
    },

    getRowStyleClass() {
      return 'bg-white';
    },

    perPageDropdown() {
      return [30, 50, 100];
    },

    columns() {
      return [
        {
          label: this.$t('settings.firstname'),
          field: 'first_name'
        },
        {
          label: this.$t('settings.lastname'),
          field: 'last_name'
        },
        {
          label: this.$t('settings.email'),
          field: 'email'
        },
        {
          label: this.$t('settings.admin_users_status'),
          field: 'account.status',
          formatFn: this.formatStatus
        },
        {
          label: this.$t('settings.admin_users_admin'),
          field: 'is_admin',
          formatFn: this.formatBoolean
        }
      ];
    }
  },

  mounted() {
  },

  methods: {
    formatBoolean(value) {
      return value ? this.$t('app.yes') : this.$t('app.no');
    },

    formatStatus(value) {
      switch (value) {
      case 'subscribed':
        return this.$t('settings.admin_users_status_payed');
      case 'free':
        return this.$t('settings.admin_users_status_free');
      default:
        return this.$t('settings.admin_users_status_standard');
      }
    },

    onRowClick(params) {
      this.$router.push({ name: 'user', params: { id: parseInt(params.row.id) } });
    },

    onPageChange(params) {
      this.$refs.list.updateParams({ page: params.currentPage });
      this.$refs.list.search();
    },

    onPerPageChange(params) {
      this.$refs.list.updateParams({ perPage: params.currentPerPage, page: 1 });
      this.$refs.list.search();
    },

    onSearch(params) {
      this.$refs.list.updateParams({ search: params.searchTerm });
      this.$refs.list.search();
    }
  }
};
</script>
