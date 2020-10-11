<style scoped>
</style>

<template>
  <form-select
    :id="'activity-type-list'"
    :title="title"
    :options="activityCategories"
    :iclass="'br2 f5 w-100 ba b--black-40 pa2 outline-0'"
    @input="$emit('input', $event)"
  />
</template>

<script>
export default {
  props: {
    title: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      activityCategories: null,
    };
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.getActivities();
    },

    getActivities() {
      axios.get('activityCategories')
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
