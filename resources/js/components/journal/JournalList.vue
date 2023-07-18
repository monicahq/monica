<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity .4s
}
</style>

<template>
  <div class="mw9 center">
    <!-- Left sidebar -->
    <div :class="[dirltr ? 'fl' : 'fr']" class="w-70-ns w-100 pa2">

      <!-- Filters -->
      <div class="filter mb-4">
        <div class="d-flex pb-2">
          <div class="dt">
            <label for="start-date">{{ $t('journal.start_date') }}:</label>
            <input type="date" id="start-date" class="form-control" v-model="startDate">
          </div>
          <div class="dt pl-2">
            <label for="end-date py-2">{{ $t('journal.end_date') }}:</label>
            <input type="date" id="end-date" class="form-control" v-model="endDate">
          </div>
          <div class="dt pl-2">
            <label for="per-page">{{ $t('journal.per_page') }}:</label>
            <input type="number" id="per-page" class="form-control" v-model="perPage">
          </div>
          <div class="dt pl-2">
            <label for="sort-order">{{ $t('journal.sort_order') }}:</label>
            <select id="sort-order" class="form-control" v-model="sortOrder">
              <option value="asc">{{ $t('journal.ascending') }}</option>
              <option value="desc">{{ $t('journal.descending') }}</option>
            </select>
          </div>
      </div>
      <button @click="getEntries" class="btn btn-primary">{{ $t('journal.apply_filter') }}</button>
      </div>


      <!-- How was your day -->
      <journal-rate-day @hasRated="hasRated" />

      <!-- Logs -->
      <div v-if="journalEntries.data" v-cy-name="'journal-entries-body'" v-cy-items="journalEntries.data.map(j => j.id)"
        :cy-object-items="journalEntries.data.map(j => j.object.id)">
        <div v-for="journalEntry in journalEntries.data" :key="journalEntry.id"
          v-cy-name="'entry-body-' + journalEntry.id" class="cf">
          <journal-content-rate v-if="journalEntry.journalable_type === 'App\\Models\\Journal\\Day'"
            :journal-entry="journalEntry" @deleteJournalEntry="deleteJournalEntry" />

          <journal-content-activity v-else-if="journalEntry.journalable_type === 'App\\Models\\Account\\Activity'"
            :journal-entry="journalEntry" />

          <journal-content-entry v-else-if="journalEntry.journalable_type === 'App\\Models\\Journal\\Entry'"
            :journal-entry="journalEntry" @deleteJournalEntry="deleteJournalEntry" />
        </div>
      </div>

      <div v-if="(journalEntries.per_page * journalEntries.current_page) <= journalEntries.total"
        class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3 tc">
        <p class="mb0 pointer" @click="loadMore()">
          <span v-if="!loadingMore">
            {{ $t('app.load_more') }}
          </span>
          <span v-else class="black-50">
            {{ $t('app.loading') }}
          </span>
        </p>
      </div>

      <div v-if="journalEntries.total === 0" v-cy-name="'journal-blank-state'"
        class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3 tc">
        <div class="tc mb4">
          <img src="img/journal/blank.svg" :alt="$t('journal.journal_empty')" />
        </div>
        <h3>
          {{ $t('journal.journal_blank_cta') }}
        </h3>
        <p>{{ $t('journal.journal_blank_description') }}</p>
      </div>
    </div>

    <!-- Right sidebar -->
    <div :class="[dirltr ? 'fl' : 'fr']" class="w-30-ns w-100 pa2">
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
      startDate: '',
      endDate: '',
      sortBy: 'created_at', // Specify the field to sort by (e.g., 'created_at', 'updated_at')
      sortOrder: 'desc', // Specify the sort order ('asc' or 'desc')
      perPage: 30, // Specify the number of entries per page
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    },
    hasMorePage: function () {
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
      axios.get('journal/entries', {
        params: {
          start_date: this.startDate,
          end_date: this.endDate,
          per_page: this.perPage,
          sort_order: this.sortOrder,
          sort_by: this.sortBy,
        },
      })
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
    deleteJournalEntry: function ($journalEntryId) {
      // check if the deleted entry date is today. If that's the case
      // we need to put back the Rate box. This is only necessary if
      // the user does all his actions on the same page without ever
      // reloading the page.
      this.journalEntries.data.filter(function (obj) {
        return obj.id === $journalEntryId;
      });

      // Filter out the array without the deleted Journal Entry
      this.journalEntries.data = this.journalEntries.data.filter(function (element) {
        return element.id !== $journalEntryId;
      });
    },

    hasRated: function (journalObject) {
      this.journalEntries.data.unshift(journalObject);
    },

    loadMore() {
      this.loadingMore = true;
      axios.get('journal/entries?page=' + (this.journalEntries.current_page + 1),{
        params: {
          start_date: this.startDate,
          end_date: this.endDate,
          per_page: this.perPage,
          sort_order: this.sortOrder,
          sort_by: this.sortBy,
        },
      })
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
