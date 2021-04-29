<template>
  <div>
    <h3 class="mb3">
      {{ $t('settings.api_authorized_clients') }}
    </h3>
    <p>{{ $t('settings.api_authorized_clients_desc') }}</p>

    <!-- Authorized Clients -->
    <p v-if="tokens.length === 0" class="mb0">
      {{ $t('settings.api_authorized_clients_none') }}
    </p>

    <div v-else class="dt dt--fixed w-100 collapse br--top br--bottom">
      <em>{{ $t('settings.api_authorized_clients_title') }}</em>
      <div class="dt-row">
        <div class="dtc w-20">
          <div class="pa2 b">
            {{ $t('settings.api_authorized_clients_name') }}
          </div>
        </div>
        <div class="dtc w-20">
          <div class="pa2 b">
            {{ $t('settings.api_authorized_clients_scopes') }}
          </div>
        </div>
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2 b">
            {{ $t('settings.personalization_contact_field_type_table_actions') }}
          </div>
        </div>
      </div>

      <div v-for="token in tokens" :key="token.id" class="dt-row bb b--light-gray">
        <!-- Client Name -->
        <div class="dtc">
          <div class="pa2">
            {{ token.client.name }}
          </div>
        </div>

        <!-- Scopes -->
        <div class="dtc">
          <div class="pa2">
            <span v-if="token.scopes.length > 0">
              {{ token.scopes.join(', ') }}
            </span>
          </div>
        </div>

        <!-- Revoke Button -->
        <div class="dtc" :class="[ dirltr ? 'tr' : 'tl' ]">
          <div class="pa2">
            <span class="pointer" @click="revoke(token)">{{ $t('app.revoke') }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {

  data() {
    return {
      tokens: []
    };
  },

  mounted() {
    this.getTokens();
  },

  methods: {
    /**
     * Get all of the authorized tokens for the user.
     */
    getTokens() {
      axios.get('oauth/tokens')
        .then(response => {
          this.tokens = response.data;
        });
    },

    /**
     * Revoke the given token.
     */
    revoke(token) {
      axios.delete('oauth/tokens/' + token.id)
        .then(response => {
          this.getTokens();
        });
    }
  }
};
</script>
