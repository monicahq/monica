<style scoped>
.code {
    margin-bottom: 0;
}
</style>

<template>
    <div>
        <h3>{{ $t('settings.recovery_title') }}</h3>
        <div class="form-group">
            <a @click="showRecoveryModal" class="btn btn-primary">{{ $t('settings.recovery_show') }}</a>
        </div>

        <sweet-modal id="recoveryModal" ref="recoveryModal" overlay-theme="dark" :title="$t('settings.recovery_title')">
            <notifications group="recovery" position="top middle" duration="5000" width="400" />

            <p>{{ $t('settings.recovery_help') }}</p>
            <p>
                <pre v-for="code in codes" :key="code" class="code">{{ code }}</pre>
            </p>
            <div class="relative">
                <span class="fl">
                    <a @click="copyIntoClipboard" class="btn">{{ $t('settings.recovery_copy') }}</a>
                </span>
                <span class="fr">
                    <a @click="closeRecoveryModal" class="btn">{{ $t('app.close') }}</a>
                </span>
            </div>
        </sweet-modal>

    </div>
</template>

<script>
    import { SweetModal, SweetModalTab } from 'sweet-modal-vue';
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                codes: [],
            };
        },

        components: {
            SweetModal,
            SweetModalTab
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

        methods: {
            prepareComponent() {
            },

            showRecoveryModal() {
                this.codes = [];
                axios.get('/settings/security/recovery-codes')
                    .then(response => {
                        this.codes = response.data.codes;
                        this.$refs.recoveryModal.open();
                    }).catch(error => {
                        this.notify(error.response.data.message, false);
                    });
            },

            closeRecoveryModal() {
                this.$refs.recoveryModal.close();
            },

            copyIntoClipboard() {
                this.$copyText(this.codes)
                    .then(response => {
                        this.notify($t('settings.recovery_clipboard'), true);
                    });
            },

            notify(text, success) {
                this.$notify({
                    group: 'recovery',
                    title: text,
                    text: '',
                    type: success ? 'success' : 'error'
                });                
            }
        }
    }
</script>
