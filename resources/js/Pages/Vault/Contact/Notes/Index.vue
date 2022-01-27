<style lang="scss" scoped>
.grid {
  grid-template-columns: 300px 1fr;
}

@media (max-width: 480px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>

<template>
  <layout :layout-data="layoutData" :inside-vault="true">
    <!-- breadcrumb -->
    <nav class="sm:mt-20 sm:border-b bg-white">
      <div class="max-w-8xl mx-auto px-4 sm:px-6 py-2 hidden md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="inline mr-2 text-gray-600">You are here:</li>
            <li class="inline mr-2">
              <inertia-link :href="layoutData.vault.url.contacts" class="text-sky-500 hover:text-blue-900">Contacts</inertia-link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline mr-2">
              <inertia-link :href="layoutData.vault.url.contacts" class="text-sky-500 hover:text-blue-900">Profile of {{ data.contact_name.name }}</inertia-link>
            </li>
            <li class="inline mr-2 relative">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline relative icon-breadcrumb" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="inline">
              All the notes
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="sm:mt-18 relative">
      <div class="max-w-6xl mx-auto px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <!-- left -->
          <div class="p-3 sm:p-3">
            <div v-if="data.contact_information.length > 0" class="mb-8">
              <div v-for="module in data.contact_information" :key="module.id">
                <avatar v-if="module.type == 'avatar'" :data="avatar" />

                <contact-name v-if="module.type == 'contact_names'" :data="contactName" />
              </div>
            </div>

            <ul class="text-sm">
              <li><span class="text-sky-500 hover:text-blue-900 cursor-pointer" @click="destroy">Delete contact</span></li>
            </ul>
          </div>

          <!-- right -->
          <div class="p-3 sm:px-3 sm:py-0">
            <!-- all the pages -->
            <div class="border-b border-gray-200 mb-8">
              <ul>
                <li v-for="page in data.template_pages" :key="page.id" class="inline mr-2">
                  <inertia-link :href="page.url.show" :class="{ 'border-orange-500 hover:border-orange-500' : page.selected }" class="pb-2 px-4 inline-block border-b-2 border-transparent hover:border-gray-200">{{ page.name }}</inertia-link>
                </li>
              </ul>
            </div>

            <!-- all the modules -->
            <div v-if="data.modules.length > 0">
              <div v-for="module in data.modules" :key="module.id">
                <notes v-if="module.type == 'notes'" :data="notes" />
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
import Avatar from '@/Shared/Modules/Avatar';
import Notes from '@/Shared/Modules/Notes';

export default {
  components: {
    Layout,
    ContactName,
    Avatar,
    Notes,
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
      notes: [],
    };
  },

  created() {

    // contact information page
    if (this.data.contact_information.length > 0) {
      if (this.data.contact_information.findIndex(x => x.type == 'contact_names') > -1) {
        this.contactName = this.data.contact_information[this.data.contact_information.findIndex(x => x.type == 'contact_names')].data;
      }

      if (this.data.contact_information.findIndex(x => x.type == 'avatar') > -1) {
        this.avatar = this.data.contact_information[this.data.contact_information.findIndex(x => x.type == 'avatar')].data;
      }
    }

    // active page
    if (this.data.modules.length > 0) {
      if (this.data.modules.findIndex(x => x.type == 'notes') > -1) {
        this.notes = this.data.modules[this.data.modules.findIndex(x => x.type == 'notes')].data;
      }
    }
  },

  methods: {
    destroy() {
      if(confirm('Are you sure? This will remove everything we know about this contact.')) {

        axios.delete(this.data.url.destroy)
          .then(response => {
            localStorage.success = 'The contact has been deleted';
            this.$inertia.visit(response.data.data);
          })
          .catch(error => {
            this.loadingState = null;
            this.form.errors = error.response.data;
          });
      }
    }
  },
};
</script>
