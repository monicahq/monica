<template>
  <div>
    <h2>{{ title }}</h2>

    <div
      v-if="!ready"
      class="d-flex align-items-center justify-content-center p-5 bottom-radius"
    >
      <span>{{ $t('app.loading') }}</span>
    </div>

    <div
      v-if="ready && !entry"
      class="d-flex align-items-center justify-content-center p-5 bottom-radius"
    >
      <span>{{ $t('app.no_entry_found' ) }}</span>
    </div>

    <div v-if="ready && entry">
      <slot :entry="entry"></slot>
    </div>
  </div>
</template>

<script type="text/ecmascript-6">
export default {
  props: {
    resource: {
      required: true,
      type: String,
      default: ''
    },
    title: {
      required: true,
      type: String,
      default: ''
    },
    id: {
      required: true,
      type: Number,
      default: 0
    }
  },

  /**
   * The component's data.
   */
  data() {
    return {
      entry: null,
      ready: false
    };
  },

  watch: {
    id() {
      this.prepareEntry();
    }
  },

  /**
   * Prepare the component.
   */
  mounted() {
    this.prepareEntry();
  },

  methods: {
    prepareEntry() {
      document.title = this.title + ' - Admin';
      this.ready = false;

      this.loadEntry(response => {
        this.entry = response.data.entry;

        this.$parent.entry = response.data.entry;

        this.ready = true;

        this.updateEntry();
      });
    },

    loadEntry(after) {
      axios
        .get('/admin-api/' + this.resource + '/' + this.id)
        .then(response => {
          if (_.isFunction(after)) {
            after(response);
          }
        })
        .catch(error => {
          this.ready = true;
        });
    }
  }
};
</script>
