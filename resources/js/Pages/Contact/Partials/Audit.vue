<style lang="scss" scoped>
.contacts {
  li:last-child {
    border-bottom: 0;
  }
}
</style>

<!-- audit logs for this contact -->
<template>
  <div>
    <ul class="ma0 list pl0">
      <li class="pa2" v-for="log in localAudits.content" :key="log.id">
        <div class="mb2">
          {{ log.description }}
        </div>
        <span class="f6 db">{{ log.author_name }} on {{ log.audited_at }}</span>
      </li>
      <li class="pa2" v-if="this.localAudits.paginator.hasMorePages"><a href="#" @click.prevent="load()">{{ $t('app.view_more') }}</a></li>
    </ul>
  </div>
</template>

<script>

export default {
  props: {
    contact: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      nextPage: 0,
      localAudits: [],
    };
  },

  created: function() {
    this.nextPage = this.contact.audit_logs.paginator.currentPage + 1;
    this.localAudits = this.contact.audit_logs;
  },

  methods: {
    load() {
      this.loadingState = 'loading';

      axios.get('/people/' + this.contact.hash + '/logs?page=' + this.nextPage)
        .then(response => {
          this.nextPage = this.nextPage + 1;
          this.localAudits.content.push(response.data.data.content[0]);
          this.localAudits.paginator = response.data.data.paginator;
        })
        .catch(error => {
          this.loadingState = null;
          this.form.errors = _.flatten(_.toArray(error.response.data));
        });
    },
  }
};
</script>
