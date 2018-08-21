<style scoped>
textarea {
  transition: all;
  transition-duration: 0.2s;
  border: 1px solid #c4cdd5;
}
textarea:focus {
  border: 1px solid #5c6ac4;
}
</style>

<template>
  <div>
    <p class="mb2" v-bind:class="{ b: required }" v-if="! noLabel">{{ label }}</p>
    <textarea
            v-model="buffer"
            @input="$emit('contentChange', buffer)"
            autofocus
            :required="required"
            :name="id"
            :placeholder="placeholder"
            :id="id"
            :rows="rows"
            class="br2 f5 w-100 ba b--black-40 pa2 outline-0" :style="'width:' + width + 'px'">{{ buffer }}</textarea>
  </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                buffer: this.value
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
            label: {
                type: String,
            },
            id: {
                type: String,
            },
            placeholder: {
                type: String,
            },
            required: {
                type: Boolean,
            },
            noLabel: {
                type: Boolean,
            },
            width: {
              type: Number,
            },
            rows: {
              type: Number,
            }
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            }
        }
    }
</script>
