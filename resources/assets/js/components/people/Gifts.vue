<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <!-- Title -->
    <div>
      <img src="img/people/gifts.svg" class="icon-section icon-tasks" />
      <h3>
        {{ $t('people.gifts_title') }}
        <a :href="'people/' + hash + '/gifts/create'" cy-name="add-gift-button" class="btn f6 pt2" :class="[ dirltr ? 'fr' : 'fl' ]">
          {{ $t('people.gifts_add_gift') }}
        </a>
      </h3>
    </div>

    <!-- Listing -->
    <div>
      <ul class="mb3">
        <li class="di">
          <p :class="[activeTab == 'ideas' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']" @click.prevent="setActiveTab('ideas')">
            {{ $t('people.gifts_ideas') }} ({{ ideas.length }})
          </p>
        </li>
        <li class="di">
          <p :class="[activeTab == 'offered' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']" @click.prevent="setActiveTab('offered')">
            {{ $t('people.gifts_offered') }} ({{ offered.length }})
          </p>
        </li>
        <li class="di">
          <p :class="[activeTab == 'received' ? 'di pointer mr3 b' : 'di pointer mr3 black-50']" @click.prevent="setActiveTab('received')">
            {{ $t('people.gifts_received') }} ({{ received.length }})
          </p>
        </li>
      </ul>

      <div v-if="activeTab == 'ideas'">
        <div v-for="gift in ideas" :key="gift.id" class="ba b--gray-monica mb3 br2" :cy-name="'gift-idea-item-' + gift.id">
          <p class="mb1 bb b--gray-monica pa2">
            <strong>{{ gift.name }}</strong>

            <span v-if="gift.recipient_name">
              <span class="mr1 black-50">
                •
              </span>
              {{ $t('people.gifts_for') }} {{ gift.recipient_name }}
            </span>

            <span v-if="gift.url">
              <span class="mr1 black-50">
                •
              </span>
              <a :href="gift.url" target="_blank">
                {{ $t('people.gifts_link') }}
              </a>
            </span>
          </p>
          <div class="f6 ph2 pv1 mb1">
            <span v-if="gift.does_value_exist">
              {{ gift.value }}
              <span class="ml1 mr1 black-50">
                •
              </span>
            </span>
            <a v-if="gift.comment" class="ml1 mr1 pointer" href="" @click.prevent="toggleComment(gift)">
              {{ $t('people.gifts_view_comment') }}
            </a>
            <a class="pointer mr1" href="" @click.prevent="toggle(gift)">
              {{ $t('people.gifts_mark_offered') }}
            </a>
            <a :href="'people/' + hash + '/gifts/' + gift.id + '/edit'" :cy-name="'edit-gift-button-' + gift.id">
              {{ $t('app.edit') }}
            </a>
            <a class="mr1 pointer" :cy-name="'delete-gift-button-' + gift.id" href="" @click.prevent="showDeleteModal(gift)">
              {{ $t('app.delete') }}
            </a>
            <div v-if="gift.show_comment" class="mb1 mt1">
              {{ gift.comment }}
            </div>
          </div>
        </div>
      </div>

      <template v-else-if="activeTab == 'offered'">
        <div v-for="gift in offered" :key="gift.id" class="ba b--gray-monica mb3 br2">
          <p class="mb1 bb b--gray-monica pa2">
            <strong>{{ gift.name }}</strong>

            <span v-if="gift.recipient_name">
              <span class="mr1 black-50">
                •
              </span>
              {{ $t('people.gifts_for') }} {{ gift.recipient_name }}
            </span>

            <span v-if="gift.url">
              <span class="mr1 black-50">
                •
              </span>
              <a :href="gift.url" target="_blank">
                {{ $t('people.gifts_link') }}
              </a>
            </span>
          </p>
          <div class="f6 ph2 pv1 mb1">
            <span v-if="gift.does_value_exist">
              {{ gift.value }}
              <span class="ml1 mr1 black-50">
                •
              </span>
            </span>
            <a v-if="gift.comment" class="ml1 mr1 pointer" href="" @click.prevent="toggleComment(gift)">
              {{ $t('people.gifts_view_comment') }}
            </a>
            <a class="pointer mr1" href="" @click.prevent="toggle(gift)">
              {{ $t('people.gifts_offered_as_an_idea') }}
            </a>
            <a :href="'people/' + hash + '/gifts/' + gift.id + '/edit'" :cy-name="'edit-gift-button-' + gift.id">
              {{ $t('app.edit') }}
            </a>
            <a class="mr1 pointer" :cy-name="'delete-gift-button-' + gift.id" href="" @click.prevent="showDeleteModal(gift)">
              {{ $t('app.delete') }}
            </a>
            <div v-if="gift.show_comment" class="mb1 mt1">
              {{ gift.comment }}
            </div>
          </div>
        </div>
      </template>

      <template v-else-if="activeTab == 'received'">
        <div v-for="gift in received" :key="gift.id" class="ba b--gray-monica mb3 br2">
          <p class="mb1 bb b--gray-monica pa2">
            <strong>{{ gift.name }}</strong>

            <span v-if="gift.recipient_name">
              <span class="mr1 black-50">
                •
              </span>
              {{ $t('people.gifts_for') }} {{ gift.recipient_name }}
            </span>

            <span v-if="gift.url">
              <span class="mr1 black-50">
                •
              </span>
              <a :href="gift.url" target="_blank">
                {{ $t('people.gifts_link') }}
              </a>
            </span>
          </p>
          <div class="f6 ph2 pv1 mb1">
            <span v-if="gift.does_value_exist">
              {{ gift.value }}
              <span class="ml1 mr1 black-50">
                •
              </span>
            </span>
            <a v-if="gift.comment" class="ml1 mr1 pointer" href="" @click.prevent="toggleComment(gift)">
              {{ $t('people.gifts_view_comment') }}
            </a>
            <a :href="'people/' + hash + '/gifts/' + gift.id + '/edit'">
              {{ $t('app.edit') }}
            </a>
            <a class="mr1 pointer" href="" @click.prevent="showDeleteModal(gift)">
              {{ $t('app.delete') }}
            </a>
            <div v-if="gift.show_comment" class="mb1 mt1">
              {{ gift.comment }}
            </div>
          </div>
        </div>
      </template>
    </div>

    <sweet-modal ref="modal" overlay-theme="dark" title="Delete gift">
      <form>
        <div class="mb4">
          {{ $t('people.gifts_delete_confirmation') }}
        </div>
      </form>
      <div class="relative">
        <span class="fr">
          <a class="btn" href="" @click.prevent="closeDeleteModal()">
            {{ $t('app.cancel') }}
          </a>
          <a class="btn" :cy-name="'modal-delete-gift-button-' + giftToTrash.id" href="" @click.prevent="trash(giftToTrash)">
            {{ $t('app.delete') }}
          </a>
        </span>
      </div>
    </sweet-modal>
  </div>
</template>

<script>

import { SweetModal } from 'sweet-modal-vue';

export default {

  components: {
    SweetModal
  },

  props: {
    hash: {
      type: String,
      default: '',
    },
    giftsActiveTab: {
      type: String,
      default: 'ideas',
    },
  },

  data() {
    return {
      gifts: [],
      activeTab: '',
      giftToTrash: '',
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },

    ideas: function () {
      return this.gifts.filter(function (gift) {
        return gift.is_an_idea === true;
      });
    },

    offered: function () {
      return this.gifts.filter(function (gift) {
        return gift.has_been_offered === true;
      });
    },

    received: function () {
      return this.gifts.filter(function (gift) {
        return gift.has_been_received === true;
      });
    },
  },

  mounted() {
    this.prepareComponent();
  },

  methods: {
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
      axios.get('people/' + this.hash + '/gifts')
        .then(response => {
          this.gifts = response.data;
        });
    },

    toggle(gift) {
      axios.post('people/' + this.hash + '/gifts/' + gift.id + '/toggle')
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
      axios.delete('people/' + this.hash + '/gifts/' + gift.id)
        .then(response => {
          this.gifts.splice(this.gifts.indexOf(gift), 1);
          this.closeDeleteModal();
        });
    },

    closeDeleteModal() {
      this.$refs.modal.close();
    }
  }
};
</script>
