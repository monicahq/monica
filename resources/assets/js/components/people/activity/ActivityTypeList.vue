<style scoped>
</style>

<template>
  <div>
    <select class="br2 f5 w-100 ba b--black-40 pa2 outline-0" @change="$emit('change', $event.target.value)">
      <option value="" selected>
        -
      </option>

      <optgroup v-for="activityTypeCategory in activityCategories" :key="activityTypeCategory.id" :label="activityTypeCategory.name">
        <option v-for="type in activityTypeCategory.types" :key="type.id" :value="type.id">
          {{ type.name }}
        </option>
      </optgroup>
    </select>
  </div>
</template>

<script>
export default {
  data() {
    return {
      activityCategories: [],
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
    prepareComponent() {
      this.getActivities();
    },

    getActivities() {
      axios.get('/activityCategories')
        .then(response => {
          this.activityCategories = response.data;
        });
    },
  }
};
</script>
