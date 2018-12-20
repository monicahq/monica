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

    props: {
        name: {
            type: String,
            default: '',
        },
        label: {
            type: String,
            default: '',
        }
    },

    methods: {
        updateInput(text) {
            //this.$emit('input', text)
        }
    }
};
</script>
