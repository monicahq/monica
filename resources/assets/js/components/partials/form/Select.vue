<style scoped>
select {
  height: 34px;
  transition: all;
  transition-duration: 0.2s;
  border: 1px solid #c4cdd5;
}
select:focus {
  border: 1px solid #5c6ac4;
}
</style>

<template>
  <div>
    <label v-if="title" :for="id" class="mb2" :class="{ b: required }">
      {{ title }}
    </label>
    <select
      :id="id"
      :value="selectedOption"
      :name="id"
      required
      :class="formClass != null ? formClass : 'br2 f5 w-100 ba b--black-40 pa2 outline-0'"
      @input="event => { $emit('input', event.target.value) }"
    >
      <option v-for="option in filterExclude(options)" :key="option.id" :value="option.id">
        {{ option.name }}
      </option>
    </select>
  </div>
</template>

<script>
export default {

    props: {
        value: null,
        options: {
            type: Array,
        },
        title: {
            type: String,
        },
        id: {
            type: String,
        },
        excludedId: {
            type: String,
        },
        required: {
            type: Boolean,
        },
        formClass: {
            type: String,
        },
    },
    /*
         * The component's data.
         */
    data() {
        return {
            selectedOption: null,
        };
    },

    watch: {
        value: function (newValue) {
            this.selectedOption = newValue;
        }
    },

    /**
     * Prepare the component (Vue 2.x).
     */
    mounted() {
        this.selectedOption = this.value;
    },

    methods: {
        /**
         * Filter options
         */
        filterExclude: function (options) {
            return options.filter(function (option) {
                return option.id != excludedId;
            });
        },
    },
};
</script>
