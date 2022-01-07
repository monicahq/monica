<template>
  <div class="pa4-ns ph3 pv2 bb b--gray-monica">
    <p>{{ $t('people.avatar_question') }}</p>
    <div class="mb3 mb0-ns">
      <!-- Default avatar -->
      <form-radio
        v-model="selectedAvatar"
        :name="'avatar'"
        :value="'default'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
      >
        <template slot="label">
          {{ $t('people.avatar_default_avatar') }}
        </template>
        <div slot="extra">
          <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" :src="defaultUrl" alt="" />
        </div>
      </form-radio>

      <!-- Adorable avatar -->
      <form-radio
        v-model="selectedAvatar"
        :name="'avatar'"
        :value="'adorable'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
      >
        <template slot="label">
          {{ $t('people.avatar_adorable_avatar') }}
        </template>
        <div slot="extra">
          <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" :src="adorableUrl" alt="" />
        </div>
      </form-radio>

      <!-- Gravatar -->
      <form-radio
        v-if="gravatarUrl"
        v-model="selectedAvatar"
        :name="'avatar'"
        :value="'gravatar'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
      >
        <template slot="label">
          <span v-html="$t('people.avatar_gravatar')"></span>
        </template>
        <div slot="extra">
          <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" :src="gravatarUrl" alt="" />
        </div>
      </form-radio>

      <!-- Existing avatar -->
      <form-radio
        v-if="initialAvatar === 'photo'"
        v-model="selectedAvatar"
        :name="'avatar'"
        :value="'photo'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
      >
        <template slot="label">
          {{ $t('people.avatar_current') }}
        </template>
        <div slot="extra">
          <img class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" :src="photoUrl" alt="" />
        </div>
      </form-radio>

      <!-- Upload avatar -->
      <form-radio
        v-model="selectedAvatar"
        :name="'avatar'"
        :value="'upload'"
        :dclass="'flex mb1'"
        :iclass="dirltr ? 'mr2' : 'ml2'"
        :disabled="hasReachedAccountStorageLimit"
      >
        <template slot="label">
          {{ $t('people.avatar_photo') }}
          <span v-if="hasReachedAccountStorageLimit">
            <a href="settings/subscriptions">
              {{ $t('app.upgrade') }}
            </a>
          </span>
        </template>
        <div slot="extra">
          <input ref="uploadedImg"
                 type="file"
                 class="form-control-file"
                 name="photo"
                 :disabled="hasReachedAccountStorageLimit"
                 @change="uploadImg($event)"
          />
          <small class="form-text text-muted">
            {{ $t('people.information_edit_max_size2', { size: maxUploadSize }) }}
          </small>
          <img v-if="croppedImgUrl" class="mb4 pa2 ba b--gray-monica br3" style="width: 150px" :src="croppedImgUrl" alt="" />
        </div>
      </form-radio>
    </div>
    <sweet-modal ref="cropModal" :title="$t('people.avatar_crop_new_avatar_photo')" :blocking="true" :hide-close-button="true">
      <clipper-basic ref="clipper" :src="uploadedImgUrl" :ratio="1" :init-width="100" :init-height="100" />
      <div slot="button">
        <a class="btn" href="" @click.prevent="cancelCrop">
          {{ $t('app.cancel') }}
        </a>
        <a class="btn btn-primary" href="" @click.prevent="setCroppedImg">
          {{ $t('app.done') }}
        </a>
      </div>
    </sweet-modal>
  </div>
</template>

<script>
import { clipperBasic } from 'vuejs-clipper';
import { SweetModal } from 'sweet-modal-vue';

export default {

  components: {
    clipperBasic,
    SweetModal
  },
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
      selectedAvatar: '',
      initialAvatar: '',
      uploadedImgUrl: '',
      croppedImgUrl: '',
    };
  },

  computed: {
    dirltr() {
      return this.$root.htmldir === 'ltr';
    }
  },

  watch: {
    avatar(val) {
      this.selectedAvatar = val;
    }
  },

  mounted() {
    this.initialAvatar = this.avatar;
    this.selectedAvatar = this.avatar;
  },

  methods: {
    uploadImg: function(e) {
      if (e.target.files.length !== 0) {
        if(this.uploadedImgUrl) {
          URL.revokeObjectURL(this.uploadedImgUrl);
        }
        this.uploadedImgUrl = window.URL.createObjectURL(e.target.files[0]);
        this.$refs.cropModal.open();
      }
    },

    setCroppedImg: function () {
      const canvas = this.$refs.clipper.clip();

      canvas.toBlob((blob) => {
        const input = this.$refs.uploadedImg;
        const file = new File([blob], input.files[0].name, { type: 'image/jpeg' });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;

        this.croppedImgUrl = window.URL.createObjectURL(blob);
      }, 'image/jpeg', 1);

      this.$refs.cropModal.close();
    },

    cancelCrop() {
      const dataTransfer = new DataTransfer();
      this.$refs.uploadedImg.files = dataTransfer.files;
      this.croppedImgUrl = '';
      this.$refs.cropModal.close();
    },
  },
};
</script>
