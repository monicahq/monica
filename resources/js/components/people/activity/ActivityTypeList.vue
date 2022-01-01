<template>
  <form-select
    :id="'activity-type-list'"
    v-model="choosenCategory"
    :title="title"
    :options="activityCategories"
    :iclass="'br2 f5 w-100 ba b--black-40 pa2 outline-0'"
    @input="$emit('input', $event)"
  />
</template>

<script>
export default {
  props: {
    value: {
      type: [String, Number],
      default: '',
    },
    title: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      choosenCategory: '',
      activityCategories: null,
    };
  },

  watch: {
    value(val) {
      this.choosenCategory = val;
    },
  },

  mounted() {
    this.getActivities().then(() => {
      this.choosenCategory = this.value;
    });
  },

  methods: {
    getActivities() {
      return axios.get('activityCategories')
        .then(response => {
          this.activityCategories = Object.assign({}, _.map(response.data, a => {
            return {
              name: a.name,
              options: a.types,
            };
          }));
        });
    },
  }
};
</script>
