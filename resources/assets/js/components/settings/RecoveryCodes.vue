<style scoped>
.code {
    margin-bottom: 0.1em;
}
.used {
    text-decoration: line-through;
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

            <p>{{ $t('settings.recovery_help_intro') }}</p>
            <p :class="[ dirltr ? 'ml3' : 'mr3' ]">
                <span v-for="code in codes" :key="code.id" :cy-name="'recovery-' + code.id">
                    <pre class="code" :class="[ code.used ? 'used' : '' ]" :title="[ code.used ? usedHelp : '']">{{ code.recovery }}</pre>
                </span>
            </p>
            <p>{{ $t('settings.recovery_help_information') }}</p>
            <div class="relative">
                <span :class="[ dirltr ? 'fl' : 'fr' ]">
                    <a @click="generateNewCodes" class="btn">{{ $t('settings.recovery_generate') }}</a>
                    <br/>
                    <small class="form-text text-muted">{{ $t('settings.recovery_generate_help') }}</small>
                </span>
                <span :class="[ dirltr ? 'fr' : 'fl' ]">
                    <a @click="copyIntoClipboard" class="btn btn-primary" :title="copyHelp">{{ $t('app.copy') }}</a>
                    <!--
                    <a @click="download" class="btn">{{ $t('app.download') }}</a>
                    -->
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
                
                usedHelp: '',
                copyHelp: '',

                dirltr: true,
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
                this.dirltr = this.$root.htmldir == 'ltr';
                this.usedHelp = this.$t('settings.recovery_already_used_help');
                this.copyHelp = this.$t('settings.recovery_copy_help');
            },

            showRecoveryModal() {
                this.codes = [];
                axios.post('/settings/security/recovery-codes')
                    .then(response => {
                        this.codes = response.data;
                        this.$refs.recoveryModal.open();
                    }).catch(error => {
                        this.notify(error.response.data.message, false);
                    });
            },

            generateNewCodes() {
                this.codes = [];
                axios.post('/settings/security/generate-recovery-codes')
                    .then(response => {
                        this.codes = response.data;
                    }).catch(error => {
                        this.notify(error.response.data.message, false);
                    });
            },

            closeRecoveryModal() {
                this.$refs.recoveryModal.close();
            },

            copyIntoClipboard() {
                this.$copyText(this.getDataStream())
                    .then(response => {
                        this.notify(this.$t('settings.recovery_clipboard'), true);
                    });
            },

            getDataStream() {
                var text = this.$t('settings.recovery_help_intro')+'\n';
                var i = 1;
                this.codes.forEach(code => {
                    if (code.used) {
                        text += i + '. ---------\n';
                    } else {
                        text += i + '. ' + code.recovery + '\n';
                    }
                    i++;
                });
                return text;
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
