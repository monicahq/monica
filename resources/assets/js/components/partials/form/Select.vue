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
    <label :for="id" class="mb2" v-bind:class="{ b: required }" v-if="title">{{ title }}</label>
    <select
        :value="selectedOption"
        @input="event => { $emit('input', event.target.value) }"
        :id="id"
        :name="id"
        required
        :class="formClass != null ? formClass : 'br2 f5 w-100 ba b--black-40 pa2 outline-0'">
        <option v-for="option in options" :key="option.id" :value="option.id" v-if="option.id != excludedId">{{ option.name }}</option>
    </select>
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                selectedOption: null,
            };
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
             this.selectedOption = this.value
        },

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

        watch: {
            value: function (newValue) {
                this.selectedOption = newValue
            }
        }
    }
</script>
