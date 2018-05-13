<style scoped>
</style>

<template>
  <div>
    <p-radio :name="name" class="p-default p-round p-thick" color="primary-o">
        {{ label }}
    </p-radio>
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
            name: {
                type: String,
            },
            label: {
                type: String,
            }
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            },

            updateInput(text) {
                //this.$emit('input', text)
            }
        }
    }
</script>
