<template>
  <div>
    <!-- Left columns: showing calendar -->
    <journal-calendar :journal-entry="journalEntry" />

    <!-- Right column: showing logs -->
    <div :class="[ dirltr ? 'fl' : 'fr' ]" class="journal-calendar-content">
      <div v-tooltip.top="$t('journal.journal_created_at', { date: entry.created_at })"
           class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3 journal-line"
      >
        <div class="flex">
          <!-- Day -->
          <div class="flex-none w-10 tc">
            <h3 class="mb0 normal">
              {{ entry.day }}
            </h3>
            <p class="mb0">
              {{ entry.day_name }}
            </p>
          </div>

          <!-- Log content -->
          <div class="flex-auto">
            <p class="mb1">
              <span class="pr2 f6 avenir">
                {{ $t('journal.journal_entry_type_journal') }}
              </span>
            </p>
            <h3 class="mb1">
              {{ entry.title }}
            </h3>

            <span dir="auto" class="markdown" v-html="compiledMarkdown(entry.post)"></span>

            <ul class="f7">
              <li class="di">
                <a v-cy-name="'entry-edit-button-' + entry.id" class="pointer" :href="'journal/entries/' + entry.id + '/edit'">
                  {{ $t('app.edit') }}
                </a>
              </li>
              <li class="di">
                <confirm v-cy-name="'entry-delete-button-' + entry.id" :message="$t('journal.delete_confirmation')" @confirm="trash()">
                  {{ $t('app.delete') }}
                </confirm>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Confirm from '../../partials/Confirm.vue';

export default {

  components: {
    Confirm,
  },

  props: {
    journalEntry: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      entry: [],
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      // not necessary, just a way to add more clarity to the code
      this.entry = this.journalEntry.object;
    },

    trash() {
      axios.delete('journal/' + this.entry.id)
        .then(response => {
          this.$emit('deleteJournalEntry', this.journalEntry.id);
        });
    },

    compiledMarkdown (text) {
      return text !== undefined && text !== null ? marked(text, { sanitize: true }) : '';
    }
  }
};
</script>
