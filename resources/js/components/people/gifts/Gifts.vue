<template>
  <div>
    <notifications group="main" position="bottom right" />

    <!-- Title -->
    <div>
      <img src="img/people/gifts.svg" :alt="$t('people.gifts_title')" class="icon-section icon-tasks" />
      <h3>
        {{ $t('people.gifts_title') }}
        <a v-cy-name="'add-gift-button'" href="" class="btn f6 pt2" :class="[ dirltr ? 'fr' : 'fl' ]"
           @click.prevent="displayCreateGift = true"
        >
          {{ $t('people.gifts_add_gift') }}
        </a>
      </h3>
    </div>

    <template v-if="displayCreateGift">
      <create-gift
        :hash="hash"
        :contact-id="contactId"
        :family-contacts="familyContacts"
        :reach-limit="reachLimit"
        @update="updateList($event)"
        @cancel="displayCreateGift = false"
      />
    </template>

    <!-- Listing -->
    <div>
      <ul class="mb3">
        <li class="di">
          <p class="di pointer" :class="[activeTab === 'idea' ? 'b' : 'black-50', dirltr ? 'mr3' : 'ml3']"
             @click.prevent="setActiveTab('idea')"
          >
            {{ $t('people.gifts_ideas') }} ({{ ideas.length }})
          </p>
        </li>
        <li class="di">
          <p class="di pointer" :class="[activeTab === 'offered' ? 'b' : 'black-50', dirltr ? 'mr3' : 'ml3']"
             @click.prevent="setActiveTab('offered')"
          >
            {{ $t('people.gifts_offered') }} ({{ offered.length }})
          </p>
        </li>
        <li class="di">
          <p class="di pointer" :class="[activeTab === 'received' ? 'b' : 'black-50', dirltr ? 'mr3' : 'ml3']"
             @click.prevent="setActiveTab('received')"
          >
            {{ $t('people.gifts_received') }} ({{ received.length }})
          </p>
        </li>
      </ul>

      <div v-for="gift in filteredGifts" :key="gift.id" v-cy-name="'gift-item-' + gift.id" class="ba b--gray-monica mb3 br2">
        <gift v-if="!gift.edit"
              :gift="gift"
              @update="($event) => { updateList($event) }"
        >
          <div :class="dirltr ? 'fl' : 'fr'">
            <a v-if="gift.status === 'idea'" class="di" href="" @click.prevent="toggle(gift)">
              {{ $t('people.gifts_mark_offered') }}
            </a>
            <a v-if="gift.status === 'offered'" class="di" href="" @click.prevent="toggle(gift)">
              {{ $t('people.gifts_offered_as_an_idea') }}
            </a>
          </div>

          <div :class="dirltr ? 'fr' : 'fl'">
            <a v-cy-name="'edit-gift-button-' + gift.id" :class="dirltr ? 'mr1' : 'ml1'" class="di" href=""
               @click.prevent="$set(gift, 'edit', true)"
            >
              {{ $t('app.edit') }}
            </a>
            <a v-cy-name="'delete-gift-button-' + gift.id" :class="dirltr ? 'mr1' : 'ml1'" class="di" href=""
               @click.prevent="showDeleteModal(gift)"
            >
              {{ $t('app.delete') }}
            </a>
          </div>
        </gift>
        <create-gift
          v-else
          :hash="hash"
          :gift="gift"
          :contact-id="contactId"
          :family-contacts="familyContacts"
          :reach-limit="reachLimit"
          @update="updateGift(gift, $event)"
          @cancel="$set(gift, 'edit', false)"
        />
      </div>
    </div>

    <sweet-modal ref="modal" overlay-theme="dark" :title="$t('people.gifts_delete_title')">
      <form>
        <div class="mb4">
          {{ $t('people.gifts_delete_confirmation') }}
        </div>
      </form>
      <div slot="button">
        <a class="btn" href="" @click.prevent="closeDeleteModal()">
          {{ $t('app.cancel') }}
        </a>
        <a v-cy-name="'modal-delete-gift-button-' + giftToTrash.id" class="btn btn-primary" href="" @click.prevent="trash(giftToTrash)">
          {{ $t('app.delete') }}
        </a>
      </div>
    </sweet-modal>
  </div>
</template>

<script>

import { SweetModal } from 'sweet-modal-vue';
import Gift from './Gift.vue';
import CreateGift from './CreateGift.vue';
import moment from 'moment';

export default {

  components: {
    Gift,
    CreateGift,
    SweetModal,
  },

  props: {
    hash: {
      type: String,
      default: '',
    },
    contactId: {
      type: Number,
      default: 0,
    },
    giftsActiveTab: {
      type: String,
      default: 'idea',
    },
    familyContacts: {
      type: Array,
      default: () => [],
    },
    reachLimit: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      gifts: [],
      activeTab: '',
      giftToTrash: '',
      displayCreateGift: false,
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    },

    ideas() {
      return this.gifts.filter(gift => gift.status === 'idea');
    },

    offered() {
      return this.gifts.filter(gift => gift.status === 'offered');
    },

    received() {
      return this.gifts.filter(gift => gift.status === 'received');
    },

    filteredGifts() {
      const vm = this;
      return this.gifts.filter(gift => gift.status === vm.activeTab);
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

    setActiveTab(view) {
      this.activeTab = view === 'ideas' ? 'idea' : view;
    },

    getGifts() {
      axios.get(`people/${this.hash}/gifts`)
        .then(response => {
          this.gifts = response.data.data;
        });
    },

    toggle(gift) {
      if (gift.status === 'idea') {
        gift.status = 'offered';
        gift.date = moment().format('YYYY-MM-DD');
      } else {
        gift.status = 'idea';
        gift.date = null;
      }
      gift.contact_id = this.contactId;
      axios.put(`people/${this.hash}/gifts/${gift.id}`, gift)
        .then(response => {
          Vue.set(gift, 'status', response.data.data.status);
          Vue.set(gift, 'date', response.data.data.date);
        });
    },

    showDeleteModal(gift) {
      this.$refs.modal.open();
      this.giftToTrash = gift;
    },

    trash(gift) {
      axios.delete(`people/${this.hash}/gifts/${gift.id}`)
        .then(response => {
          this.gifts.splice(this.gifts.indexOf(gift), 1);
          this.closeDeleteModal();
        });
    },

    updateList(activity) {
      this.displayCreateGift = false;
      this.getGifts();
    },

    updateGift(gift, response) {
      this.$set(gift, 'edit', false);
      this.$set(gift, 'name', response.name);
      this.$set(gift, 'comment', response.comment);
      this.$set(gift, 'url', response.url);
      this.$set(gift, 'amount', response.amount);
      this.$set(gift, 'amount_with_currency', response.amount_with_currency);
      this.$set(gift, 'status', response.status);
      this.$set(gift, 'recipient', response.recipient);
      this.$set(gift, 'date', response.date);
      this.$set(gift, 'photos', response.photos);
      this.$emit('update', response);
    },

    closeDeleteModal() {
      this.$refs.modal.close();
    }
  }
};
</script>
