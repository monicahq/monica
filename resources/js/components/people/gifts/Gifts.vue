<style scoped>
</style>

<template>
  <div>
    <notifications group="main" position="bottom right" />

    <!-- Title -->
    <div>
      <img src="img/people/gifts.svg" :alt="$t('people.gifts_title')" class="icon-section icon-tasks" />
      <h3>
        {{ $t('people.gifts_title') }}
        <a href="" cy-name="add-gift-button" class="btn f6 pt2" :class="[ dirltr ? 'fr' : 'fl' ]"
           @click.prevent="displayCreateGift = true"
        >
          {{ $t('people.gifts_add_gift') }}
        </a>
      </h3>
    </div>

    <template v-if="displayCreateGift">
      <create-gift
        :contact-id="contactId"
        :family-contacts="familyContacts"
        :reach-limit="reachLimit"
        @update="($event) => { updateList($event) }"
        @cancel="displayCreateGift = false"
      />
    </template>

    <!-- Listing -->
    <div>
      <ul class="mb3">
        <li class="di">
          <p class="di pointer" :class="[activeTab == 'ideas' ? 'b' : 'black-50', dirltr ? 'mr3' : 'ml3']"
             @click.prevent="setActiveTab('ideas')"
          >
            {{ $t('people.gifts_ideas') }} ({{ ideas.length }})
          </p>
        </li>
        <li class="di">
          <p class="di pointer" :class="[activeTab == 'offered' ? 'b' : 'black-50', dirltr ? 'mr3' : 'ml3']"
             @click.prevent="setActiveTab('offered')"
          >
            {{ $t('people.gifts_offered') }} ({{ offered.length }})
          </p>
        </li>
        <li class="di">
          <p class="di pointer" :class="[activeTab == 'received' ? 'b' : 'black-50', dirltr ? 'mr3' : 'ml3']"
             @click.prevent="setActiveTab('received')"
          >
            {{ $t('people.gifts_received') }} ({{ received.length }})
          </p>
        </li>
      </ul>

      <template v-if="activeTab == 'ideas'">
        <div v-for="gift in ideas" :key="gift.id" class="ba b--gray-monica mb3 br2" :cy-name="'gift-idea-item-' + gift.id">
          <gift v-if="!gift.edit"
                :gift="gift"
                @update="($event) => { updateList($event) }"
          >
            <a :class="dirltr ? 'mr1' : 'ml1'" href="" @click.prevent="toggle(gift)">
              {{ $t('people.gifts_mark_offered') }}
            </a>
            <a :class="dirltr ? 'mr1' : 'ml1'" href="" :cy-name="'edit-gift-button-' + gift.id"
               @click.prevent="$set(gift, 'edit', true)"
            >
              {{ $t('app.edit') }}
            </a>
            <a :class="dirltr ? 'mr1' : 'ml1'" :cy-name="'delete-gift-button-' + gift.id" href=""
               @click.prevent="showDeleteModal(gift)"
            >
              {{ $t('app.delete') }}
            </a>
          </gift>
          <create-gift
            v-else
            :gift="gift"
            :contact-id="contactId"
            :family-contacts="familyContacts"
            @update="($event) => { $set(gift, 'edit', false); $emit('update', $event); }"
            @cancel="$set(gift, 'edit', false)"
          />
        </div>
      </template>

      <template v-else-if="activeTab == 'offered'">
        <div v-for="gift in offered" :key="gift.id" class="ba b--gray-monica mb3 br2">
          <gift v-if="!gift.edit"
                :gift="gift"
                @update="($event) => { updateList($event) }"
          >
            <a :class="dirltr ? 'mr1' : 'ml1'" href="" @click.prevent="toggle(gift)">
              {{ $t('people.gifts_offered_as_an_idea') }}
            </a>
            <a :class="dirltr ? 'mr1' : 'ml1'" href="" :cy-name="'edit-gift-button-' + gift.id"
               @click.prevent="$set(gift, 'edit', true)"
            >
              {{ $t('app.edit') }}
            </a>
            <a :class="dirltr ? 'mr1' : 'ml1'" :cy-name="'delete-gift-button-' + gift.id" href=""
               @click.prevent="showDeleteModal(gift)"
            >
              {{ $t('app.delete') }}
            </a>
          </gift>
          <create-gift
            v-else
            :gift="gift"
            :contact-id="contactId"
            :family-contacts="familyContacts"
            @update="($event) => { $set(gift, 'edit', false); $emit('update', $event); }"
            @cancel="$set(gift, 'edit', false)"
          />
        </div>
      </template>

      <template v-else-if="activeTab == 'received'">
        <div v-for="gift in received" :key="gift.id" class="ba b--gray-monica mb3 br2">
          <gift v-if="!gift.edit"
                :gift="gift"
                @update="($event) => { updateList($event) }"
          >
            <a :class="dirltr ? 'mr1' : 'ml1'" href="" :cy-name="'edit-gift-button-' + gift.id"
               @click.prevent="$set(gift, 'edit', true)"
            >
              {{ $t('app.edit') }}
            </a>
            <a :class="dirltr ? 'mr1' : 'ml1'" href=""
               @click.prevent="showDeleteModal(gift)"
            >
              {{ $t('app.delete') }}
            </a>
          </gift>
          <create-gift
            v-else
            :gift="gift"
            :contact-id="contactId"
            :family-contacts="familyContacts"
            @update="($event) => { $set(gift, 'edit', false); $emit('update', $event); }"
            @cancel="$set(gift, 'edit', false)"
          />
        </div>
      </template>
    </div>

    <sweet-modal ref="modal" overlay-theme="dark" :title="$t('people.gifts_delete_title')">
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
import Gift from './Gift.vue';
import CreateGift from './CreateGift.vue';
import { now } from 'moment';

export default {

  components: {
    Gift,
    CreateGift,
    SweetModal
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
      default: 'ideas',
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
      return this.$root.htmldir == 'ltr';
    },

    ideas: function () {
      return this.gifts.filter(function (gift) {
        return gift.status == 'idea';
      });
    },

    offered: function () {
      return this.gifts.filter(function (gift) {
        return gift.status == 'offered';
      });
    },

    received: function () {
      return this.gifts.filter(function (gift) {
        return gift.status == 'received';
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

    setActiveTab(view) {
      this.activeTab = view;
    },

    getGifts() {
      axios.get('api/contacts/' + this.contactId + '/gifts')
        .then(response => {
          this.gifts = response.data.data;
        });
    },

    toggle(gift) {
      if (gift.status == 'idea') {
        gift.status = 'offered';
        gift.date = now;
      } else {
        gift.status = 'idea';
        gift.date = null;
      }
      axios.put('api/gift/' + gift.id, gift)
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
      axios.delete('api/gifts/' + gift.id)
        .then(response => {
          this.gifts.splice(this.gifts.indexOf(gift), 1);
          this.closeDeleteModal();
        });
    },

    updateList: function (activity) {
      this.displayLogActivity = false;
      this.getGifts();
    },

    closeDeleteModal() {
      this.$refs.modal.close();
    }
  }
};
</script>
