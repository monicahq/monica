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
    <p class="b mb2">{{ inputTitle }}</p>
    <input type="text" :name="inputId" @input="updateValue()" ref="inputText" :id="inputId" v-model="inputMutatedValue" autofocus class="br2 f5 w-100 ba b--black-40 pa2 outline-0">
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                inputMutatedValue: ''
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
        },

        props: ['value', 'inputId', 'inputValue', 'inputTitle'],

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.inputMutatedValue = this.inputValue;
            },

            updateValue() {
                this.$emit('change', this.value)
            }
        }
    }
</script>
