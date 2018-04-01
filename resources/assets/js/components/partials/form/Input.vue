<style scoped>
input {
  transition: all;
  transition-duration: 0.2s;
  border: 1px solid #c4cdd5;
}
input:focus {
  border: 1px solid #5c6ac4;
}
</style>

<template>
  <div>
    <p class="mb2" v-bind:class="{ b: required }">{{ title }}</p>
    <input :type="inputType"
            :value="value"
            @input="updateInput($event.target.value)"
            ref="input"
            autofocus
            :required="required"
            :name="id"
            :id="id"
            class="br2 f5 w-100 ba b--black-40 pa2 outline-0" :style="'width:' + width + 'px'">
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
            };
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        props: {
            value: {
                type: String,
            },
            title: {
                type: String,
            },
            id: {
                type: String,
            },
            required: {
                type: Boolean,
            },
            inputType: {
                type: String,
            },
            width: {
              type: Number,
            }
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            },

            updateInput(text) {
                this.$emit('input', text)
            }
        }
    }
</script>
