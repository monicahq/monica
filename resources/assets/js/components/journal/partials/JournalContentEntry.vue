<style scoped>
</style>

<template>
  <div>

    <!-- Left columns: showing calendar -->
    <journal-calendar v-bind:journal-entry="journalEntry"></journal-calendar>

    <!-- Right column: showing logs -->
    <div class="fl journal-calendar-content">
      <div class="br3 ba b--gray-monica bg-white pr3 pb3 pt3 mb3 journal-line">
        <div class="flex">

          <!-- Day -->
          <div class="flex-none w-10 tc">
            <h3 class="mb0 normal">{{ entry.day }}</h3>
            <p class="mb0">{{ entry.day_name }}</p>
          </div>

          <!-- Log content -->
          <div class="flex-auto">
            <p class="mb1">
              <span class="pr2 f6 avenir">{{ $t('journal.journal_entry_type_journal') }}</span>
            </p>
            <h3 class="mb1">{{ entry.title }}</h3>

            <p>{{ entry.post }}</p>

            <ul class="f7">
              <li class="di">
                <a class="pointer" @click="trash()">Delete</a>
              </li>
            </ul>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                entry: []
            };
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        props: ['journalEntry'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
              // not necessary, just a way to add more clarity to the code
                this.entry = this.journalEntry.object
            },

            trash() {
                axios.delete('/journal/' + this.entry.id)
                        .then(response => {
                            this.$emit('deleteJournalEntry', this.journalEntry.id);
                        });
            }
        }
    }
</script>
