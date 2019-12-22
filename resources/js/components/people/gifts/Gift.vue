<style scoped>
</style>

<template>
  <div>
    <div v-if="!gift.edit">
      <p class="mb1 bb b--gray-monica pa2">
        <strong>{{ gift.name }}</strong>

        <span v-if="gift.recipient && gift.recipient.complete_name">
          <span class="black-50" :class="dirltr ? 'mr1' : 'ml1'">
            •
          </span>
          {{ $t('people.gifts_for') }} {{ gift.recipient.complete_name }}
        </span>

        <span v-if="gift.url">
          <span class="black-50" :class="dirltr ? 'mr1' : 'ml1'">
            •
          </span>
          <a :href="gift.url" target="_blank">
            {{ $t('people.gifts_link') }}
          </a>
        </span>
      </p>
      <div class="f6 ph2 pv1 mb1">
        <span v-if="gift.amount">
          {{ gift.amount_with_currency }}
          <span class="black-50" :class="dirltr ? 'mr1' : 'ml1'">
            •
          </span>
        </span>
        <a v-if="gift.comment" class="ml1 mr1 pointer" href="" @click.prevent="comment = !comment">
          {{ $t('people.gifts_view_comment') }}
        </a>
        <slot></slot>
        <div v-if="comment" class="mb1 mt1">
          {{ gift.comment }}
        </div>
      </div>
    </div>
    <create-gift
      v-if="gift.edit"
      :gift="gift"
      :contact-id="contactId"
      :family-contacts="familyContacts"
      @update="($event) => { $set(gift, 'edit', false); $emit('update', $event); }"
      @cancel="$set(gift, 'edit', false)"
    />
  </div>
</template>

<script>

import CreateGift from './CreateGift.vue';

export default {

  components: {
    CreateGift,
  },

  props: {
    gift: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      comment: false,
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    },
  },
};
</script>
