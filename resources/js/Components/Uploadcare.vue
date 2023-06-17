<template>
  <div @click.prevent="onClick">
    <slot />
  </div>
</template>

<script>
import uploadcare from 'uploadcare-widget';

export default {
  props: {
    publicKey: {
      type: String,
      default: '',
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    multipleMax: {
      type: Number,
    },
    multipleMin: {
      type: Number,
    },
    imagesOnly: {
      type: Boolean,
      default: false,
    },
    // Default value does not match the UploadCare API default.
    previewStep: {
      type: Boolean,
      default: true,
    },
    crop: {
      type: String,
      default: '',
    },
    imageShrink: {
      type: Boolean,
      default: false,
    },
    clearable: {
      type: Boolean,
      default: false,
    },
    tabs: {
      type: String,
      default: 'file url camera dropbox gdrive box skydrive',
    },
    inputAcceptTypes: {
      type: String,
    },
    preferredTypes: {
      type: String,
    },
    // Default value does not match the UploadCare API default.
    systemDialog: {
      type: Boolean,
      default: true,
    },
    multipartMinSize: {
      type: Number,
      default: 26214400,
    },
    secureSignature: {
      type: String,
    },
    secureExpire: {
      type: Number,
    },
    previewProxy: {
      type: String,
    },
    previewUrlCallback: {
      type: Function,
    },
    cdnBase: {
      type: String,
    },
    doNotStore: {
      type: Boolean,
      default: false,
    },
    validators: {
      type: Array,
    },
  },

  emits: ['success', 'error'],

  methods: {
    onClick() {
      const {
        publicKey,
        multiple,
        multipleMax,
        multipleMin,
        imagesOnly,
        previewStep,
        crop,
        imageShrink,
        clearable,
        tabs,
        inputAcceptTypes,
        preferredTypes,
        systemDialog,
        multipartMinSize,
        secureSignature,
        secureExpire,
        previewProxy,
        previewUrlCallback,
        cdnBase,
        doNotStore,
        validators,
      } = this;

      const options = {
        publicKey,
        multiple,
        multipleMax,
        multipleMin,
        imagesOnly,
        previewStep,
        crop,
        imageShrink,
        clearable,
        tabs,
        inputAcceptTypes,
        preferredTypes,
        systemDialog,
        multipartMinSize,
        secureSignature,
        secureExpire,
        previewProxy,
        previewUrlCallback,
        cdnBase,
        doNotStore,
      };

      if (validators && validators.length) {
        Object.assign(options, { validators });
      }

      this.fileGroup = uploadcare.openDialog([], options);

      this.fileGroup.done((filePromise) => {
        if (this.multiple) {
          const promise = filePromise.promise();
          promise.done(() => {
            const files = filePromise.files();
            files.forEach((fileProm) => {
              fileProm.done((file) => {
                this.$emit('success', file);
              });
              fileProm.fail((err) => {
                this.$emit('error', err);
              });
            });
          });
          promise.fail((err) => {
            this.$emit('error', err);
          });
        } else {
          filePromise.done((file) => {
            this.$emit('success', file);
          });
          filePromise.fail((err) => {
            this.$emit('error', err);
          });
        }
      });
      this.fileGroup.fail((err) => {
        this.$emit('error', err);
      });
    },
  },
};
</script>
