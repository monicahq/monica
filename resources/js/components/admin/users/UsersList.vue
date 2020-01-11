<style scoped>
</style>

<template>
  <div>
    <list-screen ref="list" title="Users" resource="users" v-slot:default="slotProps">
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
            No user found
          </div>
        </template >
      </vue-good-table>
    </list-screen>

  </div>
</template>

<script>
import { SweetModal } from 'sweet-modal-vue';
import ListScreen from './../ListScreen.vue';

export default {

  components: {
    SweetModal,
    ListScreen,
  },

  props: {
    wait : {
      type: Number,
      default: 200,
    },
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
      },
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
          field: 'first_name',
        },
        {
          label: this.$t('settings.lastname'),
          field: 'last_name',
        },
        {
          label: this.$t('settings.email'),
          field: 'email',
        },
        {
          label: this.$t('settings.admin_users_free_account'),
          field: 'account.has_access_to_paid_version_for_free',
          formatFn: this.formatBoolean,
        },
        {
          label: this.$t('settings.admin_users_admin'),
          field: 'is_admin',
          formatFn: this.formatBoolean,
        }
      ];
    }
  },

  mounted() {
    /*
    this.loadItems();
    this.callUserSearch = _.debounce((text) => {
      this.loadItems();
    }, this.wait);
    */
  },

  methods: {
    formatBoolean(value) {
      return value ? this.$t('app.yes') : this.$t('app.no');
    },
/*
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },
*/
    onRowClick(params) {
      this.$router.push({name: 'user', params: {id: params.row.id}});
    },

    onPageChange(params) {
      this.$refs.list.updateParams({page: params.currentPage});
      this.$refs.list.search();
    },

    onPerPageChange(params) {
      this.$refs.list.updateParams({perPage: params.currentPerPage, page: 1});
      this.$refs.list.search();
    },

    onSearch(params) {
      this.$refs.list.updateParams({search: params.searchTerm});
      this.$refs.list.search();
    },
/*
    onSearch(params) {
      this.$refs.list.updateParams({search: params.searchTerm});
      this.callUserSearch();
    },

    loadItems() {
      return axios.post('admin/users', {
        page: this.serverParams.page,
        perPage: this.serverParams.perPage,
        search: this.serverParams.search,
      }).then(response => {
        this.users = response.data.users;
        this.totalRecords = response.data.totalRecords;
      });
    },
    */
  }
};
</script>
