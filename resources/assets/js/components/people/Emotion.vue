<style scoped>
</style>

<template>
    <div>

        <div class="relative">
            <a class="pointer" @click.prevent="menu = true">Add emotion</a>
            <ul class="absolute" v-show="menu">
                <!-- PRIMARY -->
                <li v-show="emotionsMenu == 'primary'" v-for="primaryEmotion in primaryEmotions" :key="'primary' + primaryEmotion.id" @click.prevent="showSecondary(primaryEmotion)">
                    {{ $t('app.emotion_primary_' + primaryEmotion.name) }}
                </li>

                <!-- SECONDARY -->
                <li v-show="emotionsMenu == 'secondary'">
                    <a @click.prevent="emotionsMenu = 'primary'">Back</a>
                </li>
                <li v-show="emotionsMenu == 'secondary'" v-for="secondaryEmotion in secondaryEmotions" :key="'secondary' + secondaryEmotion.id" @click.prevent="showEmotion(secondaryEmotion)">
                    {{ $t('app.emotion_secondary_' + secondaryEmotion.name) }}
                </li>

                <!-- EMOTION -->
                <li v-show="emotionsMenu == 'emotions'">
                    <a @click.prevent="emotionsMenu = 'secondary'">Back</a>
                </li>
                <li v-show="emotionsMenu == 'emotions'" v-for="emotion in emotions" :key="emotion.id">
                    {{ $t('app.emotion_' + emotion.name) }}
                </li>
            </ul>
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
                menu: false,
                emotionsMenu: 'primary',
            };
        },

        mounted() {
            this.prepareComponent()
        },

        methods: {
            prepareComponent() {
                this.getPrimaryEmotions()
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
            }
        }
    }
</script>
