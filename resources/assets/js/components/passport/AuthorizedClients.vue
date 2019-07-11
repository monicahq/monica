<style scoped>
    .action-link {
        cursor: pointer;
    }

    .m-b-none {
        margin-bottom: 0;
    }
</style>

<template>
  <div>
    <div v-if="tokens.length > 0">
      <div class="panel panel-default">
        <div class="panel-heading">
          {{ $t('settings.api_authorized_clients_title') }}
        </div>

        <div class="panel-body">
          <!-- Authorized Tokens -->
          <table class="table table-borderless m-b-none">
            <thead>
              <tr>
                <th>{{ $t('settings.api_authorized_clients_name') }}</th>
                <th>{{ $t('settings.api_authorized_clients_scopes') }}</th>
                <th></th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="token in tokens" :key="token.id">
                <!-- Client Name -->
                <td style="vertical-align: middle;">
                  {{ token.client.name }}
                </td>

                <!-- Scopes -->
                <td style="vertical-align: middle;">
                  <span v-if="token.scopes.length > 0">
                    {{ token.scopes.join(', ') }}
                  </span>
                </td>

                <!-- Revoke Button -->
                <td style="vertical-align: middle;">
                  <a class="action-link text-danger" href="" @click.prevent="revoke(token)">
                    {{ $t('app.revoke') }}
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
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
