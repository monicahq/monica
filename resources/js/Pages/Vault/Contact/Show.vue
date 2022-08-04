<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600 dark:text-slate-200">
              {{ $t('app.breadcrumb_location') }}
            </li>
            <li class="mr-2 inline">
              <inertia-link :href="layoutData.vault.url.contacts" class="text-blue-500 hover:underline">
                {{ $t('app.breadcrumb_contact_index') }}
              </inertia-link>
            </li>
            <li class="relative mr-2 inline">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="icon-breadcrumb relative inline h-3 w-3"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">
              {{ $t('app.breadcrumb_contact_show', { name: data.contact_name.name }) }}
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- banner if contact is archived -->
        <!-- this is based on the `listed` boolean on the contact object -->
        <div v-if="!data.listed" class="mb-8 rounded-lg border border-gray-300 px-3 py-2 text-center">
          <span class="mr-4"> 🕸️ </span>
          {{ $t('contact.contact_archived') }}
          <span class="ml-4"> 🕷️ </span>
        </div>

        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-3">
            <div v-if="data.contact_information.length > 0" class="mb-8">
              <div v-for="module in data.contact_information" :key="module.id">
                <contact-avatar v-if="module.type == 'avatar'" :data="avatar" />

                <contact-name v-if="module.type == 'contact_names'" :data="contactName" />

                <family-summary v-if="module.type == 'family_summary'" :data="familySummary" />

                <gender-pronoun v-if="module.type == 'gender_pronoun'" :data="genderPronoun" />

                <important-dates v-if="module.type == 'important_dates'" :data="importantDates" />

                <labels v-if="module.type == 'labels'" :data="labels" />

                <job-information v-if="module.type == 'company'" :data="jobInformation" />
              </div>
            </div>

            <ul class="text-xs">
              <!-- remove avatar -->
              <li v-if="data.avatar.hasFile" class="mb-2">
                <span @click.prevent="destroyAvatar()" class="cursor-pointer text-blue-500 hover:underline"
                  >Remove avatar</span
                >
              </li>
              <!-- upload new avatar -->
              <li v-if="!data.avatar.hasFile" class="mb-2">
                <uploadcare
                  v-if="data.avatar.uploadcarePublicKey && data.avatar.canUploadFile"
                  :public-key="data.avatar.uploadcarePublicKey"
                  :tabs="'file'"
                  :multiple="false"
                  :preview-step="false"
                  @success="onSuccess"
                  @error="onError">
                  <span class="cursor-pointer text-blue-500 hover:underline">Upload photo as avatar</span>
                </uploadcare>
              </li>
              <!-- archive contact -->
              <li v-if="data.listed && data.options.can_be_archived" class="mb-2">
                <inertia-link class="cursor-pointer text-blue-500 hover:underline" @click.prevent="toggleArchive()">
                  {{ $t('contact.contact_archive_cta') }}
                </inertia-link>
              </li>
              <!-- unarchive contact -->
              <li v-if="!data.listed" class="mb-2">
                <inertia-link class="cursor-pointer text-blue-500 hover:underline" @click.prevent="toggleArchive()">
                  {{ $t('contact.contact_unarchive_cta') }}
                </inertia-link>
              </li>
              <!-- change template -->
              <li class="mb-2">
                <inertia-link :href="data.url.update_template" class="cursor-pointer text-blue-500 hover:underline">
                  {{ $t('contact.contact_change_template_cta') }}
                </inertia-link>
              </li>
              <!-- delete contact -->
              <li v-if="data.options.can_be_deleted">
                <span class="cursor-pointer text-blue-500 hover:underline" @click="destroy">{{
                  $t('contact.contact_delete_cta')
                }}</span>
              </li>
            </ul>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <!-- family summary -->
            <div v-if="data.group_summary_information.length > 0">
              <div class="mb-6 flex rounded border border-gray-200 p-3">
                <img src="/img/group.svg" class="mr-2 h-6 w-6" />
                <ul>
                  <li class="mr-2 inline">Part of</li>
                  <li
                    v-for="group in data.group_summary_information"
                    :key="group.id"
                    class="group-list-item mr-2 inline">
                    <inertia-link class="text-blue-500 hover:underline">
                      {{ group.name }}
                    </inertia-link>
                  </li>
                </ul>
              </div>
            </div>

            <!-- all the pages -->
            <div class="mb-8 w-full border-b border-gray-200">
              <div class="flex overflow-x-auto">
                <div v-for="page in data.template_pages" :key="page.id" class="mr-2 flex-none">
                  <inertia-link
                    :href="page.url.show"
                    :class="{ 'border-orange-500 hover:border-orange-500': page.selected }"
                    class="inline-block border-b-2 border-transparent px-2 pb-2 hover:border-gray-200">
                    <span class="mb-0 block rounded-sm px-3 py-1 hover:bg-gray-100">{{ page.name }}</span>
                  </inertia-link>
                </div>
              </div>
            </div>

            <!-- all the modules -->
            <div v-if="data.modules.length > 0">
              <div v-for="module in data.modules" :key="module.id">
                <notes v-if="module.type == 'notes'" :data="notes" />

                <reminders v-if="module.type == 'reminders'" :data="reminders" />

                <feed v-if="module.type == 'feed'" :data="feed" />

                <loans v-if="module.type == 'loans'" :data="loans" :layout-data="layoutData" />

                <relationships v-if="module.type == 'relationships'" :data="relationships" />

                <tasks v-if="module.type == 'tasks'" :data="tasks" />

                <calls v-if="module.type == 'calls'" :data="calls" />

                <pets v-if="module.type == 'pets'" :data="pets" />

                <goals v-if="module.type == 'goals'" :data="goals" />

                <addresses v-if="module.type == 'addresses'" :data="addresses" />

                <groups v-if="module.type == 'groups'" :data="groups" />

                <contact-information v-if="module.type == 'contact_information'" :data="contactInformation" />

                <documents v-if="module.type == 'documents'" :data="documents" />

                <photos v-if="module.type == 'photos'" :data="photos" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import ContactName from '@/Shared/Modules/ContactName';
import GenderPronoun from '@/Shared/Modules/GenderPronoun';
import ContactAvatar from '@/Shared/Modules/ContactAvatar';
import FamilySummary from '@/Shared/Modules/FamilySummary';
import Notes from '@/Shared/Modules/Notes';
import ImportantDates from '@/Shared/Modules/ImportantDates';
import Labels from '@/Shared/Modules/Labels';
import Reminders from '@/Shared/Modules/Reminders';
import Feed from '@/Shared/Modules/Feed';
import Loans from '@/Shared/Modules/Loans';
import JobInformation from '@/Shared/Modules/JobInformation';
import Relationships from '@/Shared/Modules/Relationships';
import Tasks from '@/Shared/Modules/Tasks';
import Calls from '@/Shared/Modules/Calls';
import Pets from '@/Shared/Modules/Pets';
import Goals from '@/Shared/Modules/Goals';
import Addresses from '@/Shared/Modules/Addresses';
import Groups from '@/Shared/Modules/Groups';
import ContactInformation from '@/Shared/Modules/ContactInformation';
import Documents from '@/Shared/Modules/Documents';
import Photos from '@/Shared/Modules/Photos';
import Uploadcare from 'uploadcare-vue/src/Uploadcare.vue';

export default {
  components: {
    Layout,
    ContactName,
    GenderPronoun,
    ContactAvatar,
    FamilySummary,
    Notes,
    ImportantDates,
    Labels,
    Reminders,
    Feed,
    Loans,
    JobInformation,
    Relationships,
    Tasks,
    Calls,
    Pets,
    Goals,
    Addresses,
    Groups,
    ContactInformation,
    Documents,
    Photos,
    Uploadcare,
  },

  props: {
    layoutData: {
      type: Object,
      default: null,
    },
    data: {
      type: Object,
      default: null,
    },
  },

  data() {
    return {
      avatar: [],
      contactName: [],
      familySummary: [],
      genderPronoun: [],
      importantDates: [],
      feed: [],
      labels: [],
      notes: [],
      reminders: [],
      loans: [],
      jobInformation: [],
      relationships: [],
      tasks: [],
      calls: [],
      pets: [],
      goals: [],
      addresses: [],
      groups: [],
      contactInformation: [],
      documents: [],
      photos: [],
      form: {
        searchTerm: null,
        uuid: null,
        name: null,
        original_url: null,
        cdn_url: null,
        mime_type: null,
        size: null,
        errors: [],
      },
    };
  },

  created() {
    if (this.data.contact_information.length > 0) {
      if (this.data.contact_information.findIndex((x) => x.type == 'contact_names') > -1) {
        this.contactName =
          this.data.contact_information[this.data.contact_information.findIndex((x) => x.type == 'contact_names')].data;
      }

      if (this.data.contact_information.findIndex((x) => x.type == 'avatar') > -1) {
        this.avatar =
          this.data.contact_information[this.data.contact_information.findIndex((x) => x.type == 'avatar')].data;
      }

      if (this.data.contact_information.findIndex((x) => x.type == 'gender_pronoun') > -1) {
        this.genderPronoun =
          this.data.contact_information[
            this.data.contact_information.findIndex((x) => x.type == 'gender_pronoun')
          ].data;
      }

      if (this.data.contact_information.findIndex((x) => x.type == 'important_dates') > -1) {
        this.importantDates =
          this.data.contact_information[
            this.data.contact_information.findIndex((x) => x.type == 'important_dates')
          ].data;
      }

      if (this.data.contact_information.findIndex((x) => x.type == 'labels') > -1) {
        this.labels =
          this.data.contact_information[this.data.contact_information.findIndex((x) => x.type == 'labels')].data;
      }

      if (this.data.contact_information.findIndex((x) => x.type == 'company') > -1) {
        this.jobInformation =
          this.data.contact_information[this.data.contact_information.findIndex((x) => x.type == 'company')].data;
      }

      if (this.data.contact_information.findIndex((x) => x.type == 'family_summary') > -1) {
        this.familySummary =
          this.data.contact_information[
            this.data.contact_information.findIndex((x) => x.type == 'family_summary')
          ].data;
      }
    }

    // active page
    if (this.data.modules.length > 0) {
      if (this.data.modules.findIndex((x) => x.type == 'notes') > -1) {
        this.notes = this.data.modules[this.data.modules.findIndex((x) => x.type == 'notes')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'reminders') > -1) {
        this.reminders = this.data.modules[this.data.modules.findIndex((x) => x.type == 'reminders')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'feed') > -1) {
        this.feed = this.data.modules[this.data.modules.findIndex((x) => x.type == 'feed')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'loans') > -1) {
        this.loans = this.data.modules[this.data.modules.findIndex((x) => x.type == 'loans')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'relationships') > -1) {
        this.relationships = this.data.modules[this.data.modules.findIndex((x) => x.type == 'relationships')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'tasks') > -1) {
        this.tasks = this.data.modules[this.data.modules.findIndex((x) => x.type == 'tasks')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'calls') > -1) {
        this.calls = this.data.modules[this.data.modules.findIndex((x) => x.type == 'calls')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'pets') > -1) {
        this.pets = this.data.modules[this.data.modules.findIndex((x) => x.type == 'pets')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'goals') > -1) {
        this.goals = this.data.modules[this.data.modules.findIndex((x) => x.type == 'goals')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'addresses') > -1) {
        this.addresses = this.data.modules[this.data.modules.findIndex((x) => x.type == 'addresses')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'groups') > -1) {
        this.groups = this.data.modules[this.data.modules.findIndex((x) => x.type == 'groups')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'contact_information') > -1) {
        this.contactInformation =
          this.data.modules[this.data.modules.findIndex((x) => x.type == 'contact_information')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'documents') > -1) {
        this.documents = this.data.modules[this.data.modules.findIndex((x) => x.type == 'documents')].data;
      }

      if (this.data.modules.findIndex((x) => x.type == 'photos') > -1) {
        this.photos = this.data.modules[this.data.modules.findIndex((x) => x.type == 'photos')].data;
      }
    }
  },

  methods: {
    destroy() {
      if (confirm(this.$t('contact.contact_delete_confirm'))) {
        axios
          .delete(this.data.url.destroy)
          .then((response) => {
            localStorage.success = this.$t('contact.contact_delete_success');
            this.$inertia.visit(response.data.data);
          })
          .catch((error) => {
            this.form.errors = error.response.data;
          });
      }
    },

    toggleArchive() {
      if (confirm(this.$t('contact.contact_toggle_confirm'))) {
        axios
          .put(this.data.url.toggle_archive)
          .then((response) => {
            localStorage.success = this.$t('app.notification_flash_changes_saved');
            this.$inertia.visit(response.data.data);
          })
          .catch((error) => {
            this.form.errors = error.response.data;
          });
      }
    },

    onSuccess(file) {
      this.form.uuid = file.uuid;
      this.form.name = file.name;
      this.form.original_url = file.originalUrl;
      this.form.cdn_url = file.cdnUrl;
      this.form.mime_type = file.mimeType;
      this.form.size = file.size;

      this.upload();
    },

    upload() {
      axios
        .put(this.data.url.update_avatar, this.form)
        .then((response) => {
          this.$inertia.visit(response.data.data);
          this.flash(this.$t('contact.photos_new_success'), 'success');
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    destroyAvatar() {
      axios
        .delete(this.data.url.destroy_avatar)
        .then((response) => {
          this.$inertia.visit(response.data.data);
          localStorage.success = this.$t('app.notification_flash_changes_saved');
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.special-grid {
  grid-template-columns: 300px 1fr;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}

.group-list-item:not(:last-child):after {
  content: ',';
}
</style>
