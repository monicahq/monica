<style scoped>
</style>

<template>
    <div>
        <notifications group="favorite" position="top middle" duration="5000" width="400" />
        <p>
            <a class="pointer" @click="store(!isFavorite)" v-if="!isFavorite">{{ $t('people.set_favorite') }}</a>
            <a class="pointer" @click="store(!isFavorite)" v-if="isFavorite">{{ $t('people.unset_favorite') }}</a>
        </p>
    </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                isFavorite: false,
                dirltr: true,
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
            hash: {
                type: String,
            },
            starred: {
                type: Boolean,
            },
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.dirltr = $('html').attr('dir') == 'ltr';
                this.isFavorite = this.starred;
            },

            store(toggle) {
                axios.post('/people/' + this.hash + '/favorite', {'toggle': toggle})
                      .then(response => {
                          this.isFavorite = response.data.is_starred

                          this.$notify({
                              group: 'main',
                              title: this.$t('app.default_save_success'),
                              text: '',
                              type: 'success'
                          });
                      });
            },
        }
    }
</script>
