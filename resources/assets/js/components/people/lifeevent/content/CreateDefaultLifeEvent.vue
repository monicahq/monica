<style scoped>
</style>

<template>
    <div>
        <div class="ph4 pv3 mb3 mb0-ns bb b--gray-monica">
            <label for="another" class="mr2">{{ $t('people.life_event_create_default_title') }}</label>
            <input v-model="defaultEvent.name" @input="broadcastContentChange" autofocus class="br2 f5 w-100 ba b--black-40 pa2 outline-0">
        </div>

        <div class="ph4 pv3 mb3 mb0-ns bb b--gray-monica">
            <label for="another" class="mr2">{{ $t('people.life_event_create_default_story') }}</label>
            <form-textarea
              :required="false"
              :noLabel="true"
              :rows="4"
              :placeholder="$t('people.life_event_create_default_description')"
              @contentChange="updateNote($event)">
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

        mounted() {
            this.prepareComponent()
            this.dirltr = this.$root.htmldir == 'ltr'
        },

        methods: {
            prepareComponent() {
                this.defaultEvent.happened_at = moment().format('YYYY-MM-DD')
            },

            updateNote(event) {
                this.defaultEvent.note = event
                this.broadcastContentChange()
            },

            broadcastContentChange() {
                this.$emit('contentChange', this.defaultEvent);
            },
        }
    }
</script>
