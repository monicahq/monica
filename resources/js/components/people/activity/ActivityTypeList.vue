<style scoped>
</style>

<template>
  <form-select
    :title="title"
    :options="activityCategories"
    :iclass="'br2 f5 w-100 ba b--black-40 pa2 outline-0'"
    @input="$emit('input', $event)"
  ></form-select>
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
          this.activityCategories = _.map(response.data, a => {
            name: a.name
            options: a.types
          });
        });
    },
  }
};
</script>
