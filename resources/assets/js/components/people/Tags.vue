<style scoped>
</style>

<template>
    <div class="di relative">
        <tags-input element-id="tags"
            v-model="contactTags"
            :existing-tags="tags"
            :typeahead="true">
        </tags-input>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                tags: [],
                contactTags: [],
            };
        },

        mounted() {
            this.prepareComponent();
        },

        props: {
            hash: {
                type: String,
            },
        },

        methods: {
            prepareComponent() {
                this.getExisting()
                this.getContactTags()
            },

            getExisting() {
                axios.get('/tags')
                    .then(response => {
                        this.tags = response.data
                    })
            },

            getContactTags() {
                axios.get('/people/' + this.hash + '/tags')
                    .then(response => {
                        this.contactTags = response.data
                    })
            },

            store(toggle) {
                axios.post('/people/' + this.hash + '/favorite', {'toggle': toggle})
                      .then(response => {
                          this.isFavorite = response.data.is_starred

                          this.$notify({
                              group: 'favorite',
                              title: this.$t('app.default_save_success'),
                              text: '',
                              type: 'success'
                          });
                      });
            },
        }
    }
</script>
