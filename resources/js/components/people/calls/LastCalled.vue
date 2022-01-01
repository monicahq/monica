<template>
  <span v-cy-name="'last-talked-to'">{{ lastCalledMessage }}</span>
</template>

<script>
export default {
  props: {
    hash: {
      type: String,
      default: '',
    },
    initialValue: {
      type: String,
      default: '',
    }
  },

  data() {
    return {
      lastCalled: '',
    };
  },

  computed: {
    lastCalledMessage() {
      if (!this.initialValue && !this.lastCalled) {
        return this.$t('people.last_called_empty');
      }

      if (!this.lastCalled) {
        return this.$t('people.last_talked_to', {date: this.initialValue});
      }

      return this.$t('people.last_talked_to', {date: this.lastCalled});
    }
  },

  methods: {
    getLastCalled() {
      axios.get('people/' + this.hash + '/calls/last')
        .then(response => {
          this.lastCalled = response.data.last_talked_to;
        });
    }
  }
};
</script>
