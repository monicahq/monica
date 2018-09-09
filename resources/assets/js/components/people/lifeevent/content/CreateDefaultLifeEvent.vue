<style scoped>
</style>

<template>
    <div>
        <div class="ph4 pv3 mb3 mb0-ns bb b--gray-monica">
            <label for="another" class="mr2">Title (optional)</label>
            <form-input
              value=""
              v-bind:input-type="'text'"
              v-model="defaultEvent.name"
              v-bind:required="false">
            </form-input>
        </div>

        <div class="ph4 pv3 mb3 mb0-ns bb b--gray-monica">
            <label for="another" class="mr2">Story (optional)</label>
            <form-textarea
              v-bind:required="true"
              v-bind:noLabel="true"
              v-bind:rows="4"
              v-bind:placeholder="'Placeholder'"
              v-on:contentChange="broadcastContentChange($event)">
            </form-textarea>
        </div>
    </div>
</template>

<script>
    import moment from 'moment';

    export default {
        data() {
            return {
                defaultEvent: {
                    name: '',
                    note: '',
                    specific_information: '',
                },
                dirltr: true,
            };
        },

        props: {
            days: {
                type: Array,
            },
            months: {
                type: Array,
            },
            years: {
                type: Array,
            },
            day: {
                type: Number,
            },
            month: {
                type: Number,
            },
            year: {
                type: Number,
            },
        },

        mounted() {
            this.prepareComponent()
            this.dirltr = this.$root.htmldir == 'ltr'
        },

        methods: {
            prepareComponent() {
                this.defaultEvent.happened_at = moment().format('YYYY-MM-DD')
            },

            broadcastContentChange(note) {
                this.defaultEvent.note = note
            },
        }
    }
</script>
