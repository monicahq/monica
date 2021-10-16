<template>
  <span>{{ lastCalledMessage }}</span>
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
        return this.$t('people.vue.last_called', {date: this.initialValue});
      }

      return this.$t('people.vue.last_called', {date: this.lastCalled});
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
