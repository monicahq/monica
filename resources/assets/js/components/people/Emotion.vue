<style scoped>
.emotion-action-menu {
    border-radius: 3px;
    box-shadow: 1px 0px 1px rgba(43, 45, 80, 0.16), -1px 1px 1px rgba(43, 45, 80, 0.16), 0px 1px 4px rgba(43, 45, 80, 0.18);
    top: 34px;
    left: 0px;
    width: 150px;
}

.emotion-action-menu li:last-child {
    border-bottom: 0;
}

.emotion {
    background: #E5F3F9;
    border-radius: 7px;
}

.emotion span {
    border-left-color: #A6C8D6;
}

.emotion-add-arrow {
    right: 10px;
    top: 12px;
}

.emotion-list-line:hover {
    background-color: #f1f5fd;
}
</style>

<template>
    <div>

        <div class="relative">
            <div class="relative">
                <!-- CHOSEN EMOTIONS -->
                <ul v-show="chosenEmotions.length != 0" class="mr2 di">
                    <li class="dib emotion br5 mr2" v-for="chosenEmotion in chosenEmotions" :key="chosenEmotion.id">
                        <span class="ph2 pv1 dib">{{ $t('app.emotion_' + chosenEmotion.name) }}</span>
                        <span class="bl ph2 pv1 f6 pointer" @click.prevent="removeEmotion(chosenEmotion)">❌</span>
                    </li>
                </ul>

                <div class="relative dib">
                    <a class="pointer small-btn pa2" @click.prevent="menu = true">😐 {{ $t('people.emotion_this_made_me_feel') }}</a>

                    <!-- MENU OF EMOTIONS -->
                    <ul class="absolute emotion-action-menu bg-white z-max pv1" v-show="menu">
                        <!-- PRIMARY -->
                        <li class="pa2 pointer relative emotion-list-line" v-show="emotionsMenu == 'primary'" v-for="primaryEmotion in primaryEmotions" :key="'primary' + primaryEmotion.id" @click.prevent="showSecondary(primaryEmotion)">
                            {{ $t('app.emotion_primary_' + primaryEmotion.name) }}

                            <svg class="absolute emotion-add-arrow" width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.75071 5.66783C9.34483 6.06361 9.34483 6.93653 8.75072 7.33231L1.80442 11.9598C1.13984 12.4025 0.25 11.9261 0.25 11.1275L0.25 1.87263C0.25 1.07409 1.13984 0.59767 1.80442 1.04039L8.75071 5.66783Z" fill="#C4C4C4"/>
                            </svg>
                        </li>

                        <!-- SECONDARY -->
                        <li class="pa2 pointer bb b--gray-monica" v-show="emotionsMenu == 'secondary'">
                            <a @click.prevent="emotionsMenu = 'primary'" class="no-underline">← {{ $t('app.back') }}</a>
                        </li>
                        <li class="pa2 pointer relative emotion-list-line" v-show="emotionsMenu == 'secondary'" v-for="secondaryEmotion in secondaryEmotions" :key="'secondary' + secondaryEmotion.id" @click.prevent="showEmotion(secondaryEmotion)">
                            {{ $t('app.emotion_secondary_' + secondaryEmotion.name) }}

                            <svg class="absolute emotion-add-arrow" width="10" height="13" viewBox="0 0 10 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.75071 5.66783C9.34483 6.06361 9.34483 6.93653 8.75072 7.33231L1.80442 11.9598C1.13984 12.4025 0.25 11.9261 0.25 11.1275L0.25 1.87263C0.25 1.07409 1.13984 0.59767 1.80442 1.04039L8.75071 5.66783Z" fill="#C4C4C4"/>
                            </svg>
                        </li>

                        <!-- EMOTION -->
                        <li class="pa2 pointer bb b--gray-monica" v-show="emotionsMenu == 'emotions'">
                            <a @click.prevent="emotionsMenu = 'secondary'" class="no-underline">← {{ $t('app.back') }}</a>
                        </li>
                        <li class="pa2 pointer emotion-list-line" v-show="emotionsMenu == 'emotions'" v-for="emotion in emotions" :key="emotion.id" @click.prevent="addEmotion(emotion)">
                            {{ $t('app.emotion_' + emotion.name) }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    export default {
        data() {
            return {
                emotions: [],
                primaryEmotions: [],
                secondaryEmotions: [],
                selectedPrimaryEmotionId: 0,
                selectedSecondaryEmotionId: 0,
                chosenEmotions: [],
                menu: false,
                emotionsMenu: 'primary',
            };
        },

        props: {
            initialEmotions: {
                type: Array,
                default: function () {
                    return []
                }
            }
        },

        mounted() {
            this.prepareComponent()

            this.chosenEmotions = this.initialEmotions
        },

        created() {
            window.addEventListener('click', this.close);
        },

        beforeDestroy() {
            window.removeEventListener('click', this.close);
        },

        methods: {
            prepareComponent() {
                this.getPrimaryEmotions()
            },

            close(e) {
                if (!this.$el.contains(e.target)) {
                    this.menu = false;
                }
            },

            getPrimaryEmotions() {
                axios.get('/emotions')
                        .then(response => {
                            this.primaryEmotions = response.data.data
                        });
            },

            getSecondaryEmotions() {
                axios.get('/emotions/primaries/' + this.selectedPrimaryEmotionId + '/secondaries')
                        .then(response => {
                            this.secondaryEmotions = response.data.data
                        });
            },

            getEmotions(id) {
                axios.get('/emotions/primaries/' + this.selectedPrimaryEmotionId + '/secondaries/' + this.selectedSecondaryEmotionId + '/emotions')
                        .then(response => {
                            this.emotions = response.data.data
                        });
            },

            showSecondary(primaryEmotion) {
                this.selectedPrimaryEmotionId = primaryEmotion.id
                this.getSecondaryEmotions()
                this.emotionsMenu = 'secondary'
            },

            showEmotion(secondaryEmotion) {
                this.selectedSecondaryEmotionId = secondaryEmotion.id
                this.getEmotions()
                this.emotionsMenu = 'emotions'
            },

            addEmotion(emotion) {
                this.menu = false
                this.chosenEmotions.push(emotion)
                this.emotionsMenu = 'primary'
                this.$emit('updateEmotionsList', this.chosenEmotions);
            },

            removeEmotion(emotion) {
                this.chosenEmotions.splice(emotion, 1)
                this.$emit('updateEmotionsList', this.chosenEmotions);
            }
        }
    }
</script>
