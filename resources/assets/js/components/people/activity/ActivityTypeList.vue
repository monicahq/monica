<style scoped>
</style>

<template>
  <div>
    <select class="br2 f5 w-100 ba b--black-40 pa2 outline-0" @change="$emit('change', $event.target.value)">
      <option value="" selected>
          -
      </option>

      <optgroup v-for="activityTypeCategory in activityCategories" :label="activityTypeCategory.name" :key="activityTypeCategory.id">
        <option v-for="type in activityTypeCategory.types" :value="type.id" :key="type.id">
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
      dirltr: true,
      activityCategories: [],
    };
  },

  mounted() {
    this.prepareComponent(this.hash);
  },

  methods: {
    prepareComponent(hash) {
      this.dirltr = this.$root.htmldir == 'ltr';
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
