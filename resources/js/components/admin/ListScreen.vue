<template>
  <div>
    <h2>{{ title }}</h2>

    <!--
            <input type="text" class="form-control w-25"
                   v-if="tag || entries.length > 0"
                   id="searchInput"
                   placeholder="Search Tag" v-model="tag" @input.stop="search">
      -->

    <div
      v-if="!ready"
      class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius"
    >
      <span>{{ $t('app.loading') }}</span>
    </div>

    <div v-else>
      <slot :entries="entries" :total="total"></slot>
    </div>
  </div>
</template>

<script type="text/ecmascript-6">
export default {
  props: {
    resource: {
      type: String,
      default: null
    },
    title: {
      type: String,
      default: null
    },
    debounceWait: {
      type: Number,
      default: 200
    }
  },

  /**
   * The component's data.
   */
  data() {
    return {
      entries: [],
      total: 0,
      ready: false,
      serverParams: {
        search: '',
        page: 1,
        perPage: 30
      },
      searchEntries: null
    };
  },

  /**
   * Prepare the component.
   */
  mounted() {
    document.title = this.title + ' - Admin';

    this._focusOnSearch();

    this.searchEntries = _.debounce(() => {
      this._loadNewEntries();
    }, this.debounceWait);

    this._loadNewEntries();
  },

  /**
   * Clean after the component is destroyed.
   */
  destroyed() {
    document.onkeyup = null;
  },

  methods: {
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    _loadEntries(after) {
      axios
        .post('/admin-api/' + this.resource, this.serverParams)
        .then(response => {
          if (_.isFunction(after)) {
            after(
              _.uniqBy(response.data.entries, entry => _.uniqueId()),
              response.data.total
            );
          }
        });
    },

    /**
     * Load new entries.
     */
    _loadNewEntries() {
      this._loadEntries((entries, total) => {
        this.entries = entries;
        this.total = total;
        this.ready = true;
      });
    },

    search() {
      this.searchEntries();
    },

    /**
     * Focus on the search input when "/" key is hit.
     */
    _focusOnSearch() {
      document.onkeyup = event => {
        if (event.which === 191 || event.keyCode === 191) {
          let searchInput = document.getElementById('searchInput');

          if (searchInput) {
            searchInput.focus();
          }
        }
      };
    }
  }
};
</script>
