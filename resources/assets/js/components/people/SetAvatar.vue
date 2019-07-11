<style scoped>
</style>

<template>
  <div class="pa4-ns ph3 pv2 bb b--gray-monica">
    <p>{{ $t('people.avatar_question') }}</p>
    <div class="mb3 mb0-ns">
      <!-- Default avatar -->
      <form-radio
        v-model="avatar"
        :name="'avatar'"
        :value="'default'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
      >
        <template slot="label">
          {{ $t('people.avatar_default_avatar') }}
        </template>
        <div slot="extra">
          <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" :src="default_url" alt="" />
        </div>
      </form-radio>

      <!-- Adorable avatar -->
      <form-radio
        v-model="avatar"
        :name="'avatar'"
        :value="'adorable'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
      >
        <template slot="label">
          {{ $t('people.avatar_adorable_avatar') }}
        </template>
        <div slot="extra">
          <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" :src="adorable_url" alt="" />
        </div>
      </form-radio>

      <!-- Gravatar -->
      <form-radio
        v-if="gravatar_url"
        v-model="avatar"
        :name="'avatar'"
        :value="'gravatar'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
      >
        <template slot="label">
          {{ $t('people.avatar_gravatar') }}
        </template>
        <div slot="extra">
          <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" :src="gravatar_url" alt="" />
        </div>
      </form-radio>

      <!-- Existing avatar -->
      <form-radio
        v-if="initialAvatar === 'photo'"
        v-model="avatar"
        :name="'avatar'"
        :value="'photo'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
      >
        <template slot="label">
          {{ $t('people.avatar_current') }}
        </template>
        <div slot="extra">
          <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" :src="photo_url" alt="" />
        </div>
      </form-radio>

      <!-- Upload avatar -->
      <form-radio
        v-model="avatar"
        :name="'avatar'"
        :value="'upload'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
        :disabled="has_reached_account_storage_limit"
      >
        <template slot="label">
          {{ $t('people.avatar_photo') }}
          <span v-if="has_reached_account_storage_limit">
            <a href="/settings/subscriptions">
              {{ $t('app.upgrade') }}
            </a>
          </span>
        </template>
        <div slot="extra">
          <input type="file" class="form-control-file" name="photo" :disabled="has_reached_account_storage_limit" />
          <small class="form-text text-muted">
            {{ $t('people.information_edit_max_size', { size: max_upload_size }) }}
          </small>
        </div>
      </form-radio>
    </div>
  </div>
</template>

<script>
export default {

  props: {
    avatar: {
      type: String,
      default: '',
    },
    defaultUrl: {
      type: String,
      default: '',
    },
    adorableUrl: {
      type: String,
      default: '',
    },
    gravatarUrl: {
      type: String,
      default: '',
    },
    photoUrl: {
      type: String,
      default: '',
    },
    hasReachedAccountStorageLimit: {
      type: Boolean,
      default: false,
    },
    maxUploadSize: {
      type: Number,
      default: 10000,
    },
  },

  data() {
    return {
      initialAvatar: '',
      information_edit_max_size: '',
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir == 'ltr';
    }
  },

  mounted() {
    this.initialAvatar = this.avatar;
  },
};
</script>
