<style lang="scss" scoped>
.special-grid {
  grid-template-columns: 300px 1fr;
}

@media (max-width: 480px) {
  .special-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="bg-white sm:mt-20 sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="layoutData.vault.url.contacts" class="text-sky-500 hover:text-blue-900">
                Contacts
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
            <li class="inline">Profile of {{ data.contact_name.name }}</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="mx-auto max-w-6xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="special-grid grid grid-cols-1 gap-6 sm:grid-cols-3">
          <!-- left -->
          <div class="p-3 sm:p-3">
            <div v-if="data.contact_information.length > 0" class="mb-8">
              <div v-for="module in data.contact_information" :key="module.id">
                <avatar v-if="module.type == 'avatar'" :data="avatar" />

                <contact-name v-if="module.type == 'contact_names'" :data="contactName" />

                <gender-pronoun v-if="module.type == 'gender_pronoun'" :data="genderPronoun" />

                <important-dates v-if="module.type == 'important_dates'" :data="importantDates" />

                <labels v-if="module.type == 'labels'" :data="labels" />

                <job-information v-if="module.type == 'company'" :data="jobInformation" />
              </div>
            </div>

            <ul class="text-sm">
              <li>
                <span class="cursor-pointer text-sky-500 hover:text-blue-900" @click="destroy">Delete contact</span>
              </li>
            </ul>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <!-- all the pages -->
            <div class="mb-8 border-b border-gray-200">
              <ul>
                <li v-for="page in data.template_pages" :key="page.id" class="mr-2 inline">
                  <inertia-link
                    :href="page.url.show"
                    :class="{ 'border-orange-500 hover:border-orange-500': page.selected }"
                    class="inline-block border-b-2 border-transparent px-4 pb-2 hover:border-gray-200">
                    <span class="mb-0 block rounded-sm px-3 py-1 hover:bg-gray-100">{{ page.name }}</span>
                  </inertia-link>
                </li>
              </ul>
            </div>

            <!-- all the modules -->
            <div v-if="data.modules.length > 0">
              <div v-for="module in data.modules" :key="module.id">
                <notes v-if="module.type == 'notes'" :data="notes" />

                <reminders v-if="module.type == 'reminders'" :data="reminders" />

                <feed v-if="module.type == 'feed'" :data="feed" />

                <loans v-if="module.type == 'loans'" :data="loans" :layout-data="layoutData" />

                <relationships v-if="module.type == 'relationships'" :data="relationships" />
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
import Avatar from '@/Shared/Modules/Avatar';
import Notes from '@/Shared/Modules/Notes';
import ImportantDates from '@/Shared/Modules/ImportantDates';
import Labels from '@/Shared/Modules/Labels';
import Reminders from '@/Shared/Modules/Reminders';
import Feed from '@/Shared/Modules/Feed';
import Loans from '@/Shared/Modules/Loans';
import JobInformation from '@/Shared/Modules/JobInformation';
import Relationships from '@/Shared/Modules/Relationships';

export default {
  components: {
    Layout,
    ContactName,
    GenderPronoun,
    Avatar,
    Notes,
    ImportantDates,
    Labels,
    Reminders,
    Feed,
    Loans,
    JobInformation,
    Relationships,
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
      genderPronoun: [],
      importantDates: [],
      feed: [],
      labels: [],
      notes: [],
      reminders: [],
      loans: [],
      jobInformation: [],
      relationships: [],
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
    }
  },

  methods: {
    destroy() {
      if (confirm('Are you sure? This will remove everything we know about this contact.')) {
        axios
          .delete(this.data.url.destroy)
          .then((response) => {
            localStorage.success = 'The contact has been deleted';
            this.$inertia.visit(response.data.data);
          })
          .catch((error) => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    },
  },
};
</script>
