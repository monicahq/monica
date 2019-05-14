<style scoped>
.url {
    width: 650px;
    display: inline-block;
    margin-bottom: 0;
    vertical-align: middle;
}
</style>

<template>
  <div>
    <h2>{{ $t('settings.dav_title') }}</h2>
    <p>{{ $t('settings.dav_description') }}</p>

    <notifications group="dav" position="top middle" :duration="5000" width="400" />

    
    <div class="form-group">
      <label for="dav_url_base">
        {{ $t('settings.dav_url_base') }}
      </label>
      <br />
      <input id="dav_url_base" :value="davRoute" class="url form-control" type="text" readonly />
      <a class="btn btn-primary" :title="$t('settings.dav_copy_help')" href="" @click.prevent="copyIntoClipboard(davRoute)">
        {{ $t('app.copy') }}
      </a>
      <p>
        {{ $t('settings.dav_connect_help') }}
      </p>
      <p>
        {{ $t('settings.dav_connect_help2') }}
      </p>
    </div>

    <div class="settings-group">
      <h2>{{ $t('settings.dav_title_carddav') }}</h2>
      <div class="form-group">
        <label for="dav_url_carddav">
          {{ $t('settings.dav_url_carddav') }}
        </label>
        <br />
        <input id="dav_url_carddav" :value="cardDavRoute" class="url form-control" type="text" readonly />
        <a class="btn btn-primary" :title="$t('settings.dav_copy_help')" href="" @click.prevent="copyIntoClipboard(cardDavRoute)">
          {{ $t('app.copy') }}
        </a>
        <br />
        <a :href="cardDavRoute+'?export'">
          {{ $t('settings.dav_carddav_export') }}
        </a>
      </div>
    </div>

    <div class="settings-group">
      <h2>{{ $t('settings.dav_title_caldav') }}</h2>
      <div class="form-group">
        <label for="dav_url_caldav_birthdays">
          {{ $t('settings.dav_url_caldav_birthdays') }}
        </label>
        <br />
        <input id="dav_url_caldav_birthdays" :value="calDavBirthdaysRoute" class="url form-control" type="text" readonly />
        <a class="btn btn-primary" :title="$t('settings.dav_copy_help')" href="" @click.prevent="copyIntoClipboard(calDavBirthdaysRoute)">
          {{ $t('app.copy') }}
        </a>
        <br />
        <a :href="calDavBirthdaysRoute+'?export'">
          {{ $t('settings.dav_caldav_birthdays_export') }}
        </a>
      </div>
      <div class="form-group">
        <label for="dav_url_caldav_tasks">
          {{ $t('settings.dav_url_caldav_tasks') }}
        </label>
        <br />
        <input id="dav_url_caldav_tasks" :value="calDavTasksRoute" class="url form-control" type="text" readonly />
        <a class="btn btn-primary" :title="$t('settings.dav_copy_help')" href="" @click.prevent="copyIntoClipboard(calDavTasksRoute)">
          {{ $t('app.copy') }}
        </a>
        <br />
        <a :href="calDavTasksRoute+'?export'">
          {{ $t('settings.dav_caldav_tasks_export') }}
        </a>
      </div>
    </div>
  </div>
</template>

<script>
export default {

  props: {
    davRoute: {
      type: String,
      default: '',
    },
    cardDavRoute: {
      type: String,
      default: '',
    },
    calDavBirthdaysRoute: {
      type: String,
      default: '',
    },
    calDavTasksRoute: {
      type: String,
      default: '',
    },
  },

  methods: {

    copyIntoClipboard(text) {
      this.$copyText(text)
        .then(response => {
          this.notify(this.$t('settings.dav_clipboard_copied'), true);
        });
    },

    notify(text, success) {
      this.$notify({
        group: 'dav',
        title: text,
        text: '',
        type: success ? 'success' : 'error'
      });
    }
  }
};
</script>
