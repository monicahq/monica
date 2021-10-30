<style lang="scss" scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity .4s
}

.journal-list {
  &:last-child {
    border-bottom: 0;
  }
}
</style>

<template>
  <div class="mw9 center">
    <!-- Left sidebar -->
    <div :class="[ dirltr ? 'fl' : 'fr' ]" class="w-70-ns w-100 pa2 cf">
      <!-- How was your day -->
      <journal-rate-day @hasRated="hasRated" />

      <div :class="[ dirltr ? 'fl' : 'fr' ]" class="w-30-ns">
        <!-- Entries -->
        <div v-if="journalEntries.data" class="ba b--gray-monica br3 bg-white">
          <div v-for="entry in journalEntries.data" :key="entry.id" class="pa3 bb b--gray-monica journal-list">
            <p class="mb1 f7 gray">{{ entry.written_at }}</p>

            <h3 class="mb1">
              {{ entry.title }}
            </h3>

            <span dir="auto" class="markdown" v-html="entry.post"></span>
          </div>
        </div>


        <!-- blank state -->
        <div v-if="journalEntries.total === 0" class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3 tc">
          <div class="tc mb4">
            <img src="img/journal/blank.svg" :alt="$t('journal.journal_empty')" />
          </div>
          <h3>
            {{ $t('journal.journal_blank_cta') }}
          </h3>
          <p>{{ $t('journal.journal_blank_description') }}</p>
        </div>
      </div>

      <div :class="[ dirltr ? 'fl' : 'fr' ]" class="w-70-ns pl3">
        <div v-html="{{ post }}" class="bg-white"></div>
      </div>
    </div>

    <!-- Right sidebar -->
    <div :class="[ dirltr ? 'fl' : 'fr' ]" class="w-30 pa2">
      <a v-cy-name="'add-entry-button'" href="journal/add" class="btn btn-primary w-100 mb4">
        {{ $t('journal.journal_add') }}
      </a>
      <p>{{ $t('journal.journal_description') }}</p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      journalEntries: [],

      day: {
        rate: ''
      },

      showSadSmileyColor: false,
      showHappySmileyColor: false,
      loadingMore: false,

    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },
    hasMorePage: function() {
      var total = this.journalEntries.per_page * this.journalEntries.current_page;

      if (total >= this.journalEntries.total) {
        return true;
      }

      return false;
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.getEntries();
    },

    getEntries() {
      axios.get('journal/entries')
        .then(response => {
          this.journalEntries = response.data;
          this.journalEntries.current_page = response.data.current_page;
          this.journalEntries.next_page_url = response.data.next_page_url;
          this.journalEntries.per_page = response.data.per_page;
          this.journalEntries.prev_page_url = response.data.prev_page_url;
          this.journalEntries.total = response.data.total;
        });
    },

    // This event is omited from the child component
    deleteentry: function($entryId) {
      // check if the deleted entry date is today. If that's the case
      // we need to put back the Rate box. This is only necessary if
      // the user does all his actions on the same page without ever
      // reloading the page.
      this.journalEntries.data.filter(function(obj) {
        return obj.id == $entryId;
      });

      // Filter out the array without the deleted Journal Entry
      this.journalEntries.data = this.journalEntries.data.filter(function(element) {
        return element.id !== $entryId;
      });
    },

    hasRated: function(journalObject) {
      this.journalEntries.data.unshift(journalObject);
    },

    loadMore() {
      this.loadingMore = true;
      axios.get('journal/entries?page=' + (this.journalEntries.current_page + 1))
        .then(response => {
          this.journalEntries.current_page = response.data.current_page;
          this.journalEntries.next_page_url = response.data.next_page_url;
          this.journalEntries.per_page = response.data.per_page;
          this.journalEntries.prev_page_url = response.data.prev_page_url;
          this.journalEntries.total = response.data.total;

          for (var j of response.data.data) {
            this.journalEntries.data.push(j);
          }

          this.loadingMore = false;
        });
    },
  }
};
</script>
