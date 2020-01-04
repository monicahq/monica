<style lang="scss" scoped>
    .dt-row {
        display: table;
    }

    .dtc {
        border-color: #eaecef;
    }

    .avatar {
        width: 40px;

        img {
            max-width: none;
        }
    }

    .names {
        top: -3px;
    }
</style>

<template>
    <ul class="dt w-100">
        <li class="dt-row w-100">
            <!-- checkbox -->
            <div class="dtc bt bg-white pl-2 pr-0 v-mid bl">
                <input type="checkbox" name="members[]" value="asbiin" aria-labelledby="member-asbiin" class="js-bulk-actions-toggle" data-check-all-item="">
            </div>

            <!-- photo -->
            <div class="dtc bt bg-white pv2 ph2 avatar relative v-mid">
                <img src="https://monica-assets-s3.s3.amazonaws.com/photos/1mIrWXIusjMAqV2xWR7lUNuls4YSAt7V30YbkTwD.png" width="40" height="40" class="dib">
            </div>

            <!-- name -->
            <div class="dtc bt bg-white pl-2 pr-0 pt-2 w-100 v-mid br">
                <div class="names relative">
                    <a href="" class="v-top">Regis Freyd</a>
                    <span class="db">asbiin</span>
                </div>
            </div>
        </li>
    </ul>
</template>

<script>

export default {

  components: {
  },

  props: {
    showArchived: {
      type: Boolean,
      default: false,
    },
    debounceWait: {
      type: Number,
      default: 200,
    },
  },

  data() {
    return {
      contacts: [],
      searchEntries: null,
      ready: false,

      totalRecords: 0,
      perPageDropdown: [30, 50, 100],

      serverParams: {
        search: '',
        page: 1,
        perPage: 30
      },

      columns: [
        {
          field: 'avatar',
          width: '70px',
        },
        {
          field: 'name',
        },
        {
          field: 'description',
        }
      ],
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.searchEntries = _.debounce(() => {
      this.ready = false;
      this.contacts = [];
      this._loadItems();
    }, this.debounceWait);

    this._loadItems();
  },

  methods: {
    onRowClick(params) {
      window.location.href = params.row.route;
    },

    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    onPageChange(params) {
      this.updateParams({page: params.currentPage});
      this.searchEntries();
    },

    onPerPageChange(params) {
      this.updateParams({perPage: params.currentPerPage, page: 1});
      this.searchEntries();
    },

    onSearch(params) {
      this.updateParams({search: params.searchTerm});
      this.searchEntries();
    },

    // load items is what brings back the rows from server
    _loadItems() {
      let urlParam = window.location.search;

      if (urlParam) {
        urlParam += '&';
      } else {
        urlParam += '?';
      }

      urlParam += 'page='+this.serverParams.page;
      urlParam += '&perPage='+this.serverParams.perPage;
      urlParam += '&search='+this.serverParams.search;
      if (this.showArchived) {
        urlParam += '&show_archived=true';
      }

      this._loadNewItems(urlParam, (entries, total) => {
        this.contacts = entries;
        this.totalRecords = total;
        this.ready = true;
      });
    },

    _loadNewItems(urlParam, after) {
      axios.get('people/list'+urlParam)
        .then(response => {
          if (_.isFunction(after)) {
            after(
              _.uniqBy(response.data.contacts, entry => _.uniqueId()),
              response.data.totalRecords
            );
          }
        });
    },

    getRowStyleClass() {
      return 'people-list-item bg-white pointer';
    }
  }
};
</script>
