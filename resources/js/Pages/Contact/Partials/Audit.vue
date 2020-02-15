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
      <li class="pa2" v-for="log in logs.content" :key="log.id">
        <div class="mb2">
          {{ log.description }}
        </div>
        <span class="f6 db">{{ log.author_name }} on {{ log.audited_at }}</span>
      </li>
    </ul>
  </div>
</template>

<script>
import TextArea from '@/Shared/TextArea';
import LoadingButton from '@/Shared/LoadingButton';
import Errors from '@/Shared/Errors';

export default {
  components: {
    TextArea,
    LoadingButton,
    Errors
  },

  props: {
    logs: {
      type: Array,
      default: [],
    },
    hash: {
      type: String,
      default: null,
    },
  },

  data() {
    return {
      localDescription: null,
      editMode: false,
      form: {
        description: null,
        errors: [],
      },
      loadingState: '',
    };
  },

  created: function() {
    this.localDescription = this.description;
    this.form.description = this.description;
  },
};
</script>
