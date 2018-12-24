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
    <p class="mb2" :class="{ b: required }">
      {{ title }}
    </p>
    <input :id="id"
           ref="input"
           :type="inputType"
           :value="value"
           autofocus
           :required="required"
           :name="id"
           class="br2 f5 w-100 ba b--black-40 pa2 outline-0"
           :style="[width >= 0 ? 'width:' + width + 'px' : '']"
           @input="updateInput($event.target.value)"
    />
  </div>
</template>

<script>
export default {

    props: {
        value: {
            type: String,
            default: '',
        },
        title: {
            type: String,
            default: '',
        },
        id: {
            type: String,
            default: '',
        },
        required: {
            type: Boolean,
            default: true,
        },
        inputType: {
            type: String,
            default: '',
        },
        width: {
            type: Number,
            default: -1,
        }
    },

    methods: {
        updateInput(text) {
            this.$emit('input', text);
        }
    }
};
</script>
