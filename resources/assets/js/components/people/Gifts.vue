<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <!-- Title -->
    <div>
      <img src="/img/people/gifts.svg" class="icon-section icon-tasks">
      <h3>
        Gifts

        <a :href="'/people/' + contactId + '/gifts/add'" class="btn fr f6 pt2">{{ $t('people.gifts_add_gift') }}</a>
      </h3>
    </div>

    <!-- Listing -->
    <div>
        <ul class="mb3">
            <li class="di">
                <p @click.prevent="setActiveTab('ideas')" v-bind:class="[activeTab == 'ideas' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']">{{ $t('people.gifts_ideas') }} ({{ ideas.length }})</p>
            </li>
            <li class="di">
                <p @click.prevent="setActiveTab('offered')" v-bind:class="[activeTab == 'offered' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']">{{ $t('people.gifts_offered') }} ({{ offered.length }})</p>
            </li>
            <li class="di">
                <p @click.prevent="setActiveTab('received')" v-bind:class="[activeTab == 'received' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']">{{ $t('people.gifts_received') }} ({{ received.length }})</p>
            </li>
        </ul>

        <div v-if="activeTab == 'ideas'">
            <div v-for="gift in ideas" class="ba b--gray-monica mb3 br2" :key="gift.id">
                <p class="mb1 bb b--gray-monica pa2">
                    <strong>{{ gift.name }}</strong>

                    <span v-if="gift.recipient_name">
                        <span class="mr1 black-50">•</span>
                        For: {{ gift.recipient_name }}
                    </span>

                    <span v-if="gift.url">
                        <span class="mr1 black-50">•</span>
                        <a :href="gift.url" target="_blank">{{ $t('people.gifts_link') }}</a>
                    </span>
                </p>
                <div class="f6 ph2 pv1 mb1">
                    <span v-if="gift.does_value_exist">
                        {{ gift.value }}
                        <span class="ml1 mr1 black-50">•</span>
                    </span>
                    <a v-if="gift.comment" @click="toggleComment(gift)" class="ml1 mr1 pointer">{{ $t('people.gifts_view_comment') }}</a>
                    <a @click="toggle(gift)" class="pointer mr1">{{ $t('people.gifts_mark_offered') }}</a>
                    <a :href="'/people/' + contactId + '/gifts/' + gift.id + '/edit'">{{ $t('app.edit') }}</a>
                    <a @click="showDeleteModal(gift)" class="mr1 pointer">{{ $t('app.delete') }}</a>
                    <div v-if="gift.show_comment" class="mb1 mt1">
                        {{ gift.comment }}
                    </div>
                </div>
            </div>

        </div>

        <div v-for="gift in offered" class="ba b--gray-monica mb3 br2" :key="gift.id" v-if="activeTab == 'offered'">
            <p class="mb1 bb b--gray-monica pa2">
                <strong>{{ gift.name }}</strong>

                <span v-if="gift.recipient_name">
                    <span class="mr1 black-50">•</span>
                    For: {{ gift.recipient_name }}
                </span>

                <span v-if="gift.url">
                    <span class="mr1 black-50">•</span>
                    <a :href="gift.url" target="_blank">{{ $t('people.gifts_link') }}</a>
                </span>
            </p>
            <div class="f6 ph2 pv1 mb1">
                <span v-if="gift.does_value_exist">
                    {{ gift.value }}
                    <span class="ml1 mr1 black-50">•</span>
                </span>
                <a v-if="gift.comment" @click="toggleComment(gift)" class="ml1 mr1 pointer">{{ $t('people.gifts_view_comment') }}</a>
                <a @click="toggle(gift)" class="pointer mr1">Mark as an idea</a>
                <a :href="'/people/' + contactId + '/gifts/' + gift.id + '/edit'">{{ $t('app.edit') }}</a>
                <a @click="showDeleteModal(gift)" class="mr1 pointer">{{ $t('app.delete') }}</a>
                <div v-if="gift.show_comment" class="mb1 mt1">
                    {{ gift.comment }}
                </div>
            </div>
        </div>

        <div v-for="gift in received" class="ba b--gray-monica mb3 br2" :key="gift.id" v-if="activeTab == 'received'">
            <p class="mb1 bb b--gray-monica pa2">
                <strong>{{ gift.name }}</strong>

                <span v-if="gift.recipient_name">
                    <span class="mr1 black-50">•</span>
                    For: {{ gift.recipient_name }}
                </span>

                <span v-if="gift.url">
                    <span class="mr1 black-50">•</span>
                    <a :href="gift.url" target="_blank">{{ $t('people.gifts_link') }}</a>
                </span>
            </p>
            <div class="f6 ph2 pv1 mb1">
                <span v-if="gift.does_value_exist">
                    {{ gift.value }}
                    <span class="ml1 mr1 black-50">•</span>
                </span>
                <a v-if="gift.comment" @click="toggleComment(gift)" class="ml1 mr1 pointer">{{ $t('people.gifts_view_comment') }}</a>
                <a :href="'/people/' + contactId + '/gifts/' + gift.id + '/edit'">{{ $t('app.edit') }}</a>
                <a @click="showDeleteModal(gift)" class="mr1 pointer">{{ $t('app.delete') }}</a>
                <div v-if="gift.show_comment" class="mb1 mt1">
                    {{ gift.comment }}
                </div>
            </div>
        </div>
    </div>

    <sweet-modal ref="modal" overlay-theme="dark" title="Delete gift">
      <form>
        <div class="mb4">
          {{ $t('people.gifts_delete_confirmation') }}
        </div>
      </form>
      <div class="relative">
        <span class="fr">
            <a @click="closeDeleteModal()" class="btn">{{ $t('app.cancel') }}</a>
            <a @click="trash(giftToTrash)" class="btn">{{ $t('app.delete') }}</a>
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
                gifts: [],
                activeTab: '',
                giftToTrash: ''
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

        props: ['contactId', 'giftsActiveTab'],

        computed: {
            ideas: function () {
                return this.gifts.filter(function (gift) {
                    return gift.is_an_idea === true
                })
            },

            offered: function () {
                return this.gifts.filter(function (gift) {
                    return gift.has_been_offered === true
                })
            },

            received: function () {
                return this.gifts.filter(function (gift) {
                    return gift.has_been_received === true
                })
            },
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.getGifts();
                this.setActiveTab(this.giftsActiveTab);
            },

            toggleComment(gift) {
                Vue.set(gift, 'show_comment', !gift.show_comment);
            },

            setActiveTab(view) {
                this.activeTab = view;
            },

            getGifts() {
                axios.get('/people/' + this.contactId + '/gifts')
                        .then(response => {
                            this.gifts = response.data;
                        });
            },

            toggle(gift) {
                axios.post('/people/' + this.contactId + '/gifts/' + gift.id + '/toggle')
                        .then(response => {
                            Vue.set(gift, 'is_an_idea', response.data.is_an_idea);
                            Vue.set(gift, 'has_been_offered', response.data.has_been_offered);
                        });
            },

            showDeleteModal(gift) {
                this.$refs.modal.open();
                this.giftToTrash = gift;
            },

            trash(gift) {
                axios.delete('/people/' + this.contactId + '/gifts/' + gift.id)
                      .then(response => {
                          this.gifts.splice(this.gifts.indexOf(gift), 1);
                          this.$refs.modal.close();
                      });
            },

            closeDeleteModal() {
                this.$refs.modal.close();
            }
        }
    }
</script>
