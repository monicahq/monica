<style scoped>
</style>

<template>
  <section class="cf ph3 ph5-ns w-100 mv5">
    <div class="fl w-70 pa2 outline">
      <form class="black-80">
        <div class="measure">
          <label for="name" class="f6 b db mb2">Name</label>
          <input id="name" class="input-reset ba b--black-20 pa2 mb2 db w-100" type="text">
          <small id="name-desc" class="f6 black-60 db mb2">Helper text for the form control.</small>
          <rate :length="5"></rate>
        </div>
        <div class="measure">
          <label for="comment" class="f6 b db mb2">Comment <span class="normal black-60">(optional)</span></label>
          <textarea id="comment" class="w-100"></textarea>
        </div>
      </form>
    </div>
    <div class="fl w-30 pa2 outline">
      <div class=" bg-white tc pv4">test</div>
    </div>
  </section>
</template>

<script>
    import Rate from 'vue-rate';

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

        props: ['contactId'],

        components: {
            'rate': Rate
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
            },
        }
    }
</script>
