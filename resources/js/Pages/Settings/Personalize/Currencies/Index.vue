<style lang="scss" scoped>
.item-list {
  &:hover:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-child {
    border-bottom: 0;
  }

  &:hover:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
  }
}
</style>

<template>
  <layout :layout-data="layoutData">
    <!-- breadcrumb -->
    <nav class="bg-white sm:border-b">
      <div class="max-w-8xl mx-auto hidden px-4 py-2 sm:px-6 md:block">
        <div class="flex items-baseline justify-between space-x-6">
          <ul class="text-sm">
            <li class="mr-2 inline text-gray-600">You are here:</li>
            <li class="mr-2 inline">
              <inertia-link :href="data.url.settings" class="text-blue-500 hover:underline">Settings</inertia-link>
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
            <li class="mr-2 inline">
              <inertia-link :href="data.url.personalize" class="text-blue-500 hover:underline"
                >Personalize your account</inertia-link
              >
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
            <li class="inline">Currencies</li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="relative sm:mt-20">
      <div class="mx-auto max-w-3xl px-2 py-2 sm:py-6 sm:px-6 lg:px-8">
        <!-- title + cta -->
        <div class="mb-6 mt-8 items-center justify-between sm:mt-0 sm:flex">
          <h3 class="mb-4 sm:mb-0"><span class="mr-1"> 💵 </span> All the currencies</h3>
        </div>

        <!-- help text -->
        <div class="mb-6 flex rounded border bg-slate-50 px-3 py-2 text-sm">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 grow pr-2"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>

          <div>
            <p class="mb-2">
              You can choose which currencies should be enabled in your account, and which one shouldn't.
            </p>
            <p>You can change that at any time.</p>
          </div>
        </div>

        <div class="mb-3 text-right">
          <ul>
            <li class="mr-2 inline">
              <span @click="enableAll" class="inline cursor-pointer text-blue-500 hover:underline"> Enable all </span>
            </li>
            <li class="inline">
              <span @click="disableAll" class="inline cursor-pointer text-blue-500 hover:underline"> Disable all </span>
            </li>
          </ul>
        </div>

        <!-- list of currencies -->
        <ul v-if="localCurrencies.length > 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <li
            v-for="currency in localCurrencies"
            :key="currency.id"
            class="item-list border-b border-gray-200 hover:bg-slate-50">
            <div class="flex justify-between px-3 py-2" :class="!currency.active ? 'bg-slate-200' : ''">
              <div>
                <span class="mr-2 text-sm text-gray-500">{{ currency.code }}</span>
                <span>{{ currency.name }}</span>
              </div>

              <!-- enable -->
              <span
                v-if="!currency.active"
                @click="update(currency)"
                class="mr-4 inline cursor-pointer text-blue-500 hover:underline">
                Enable
              </span>

              <!-- disable -->
              <span v-else @click="update(currency)" class="mr-4 inline cursor-pointer text-blue-500 hover:underline">
                Disable
              </span>
            </div>
          </li>
        </ul>

        <!-- blank state -->
        <div v-if="localCurrencies.length == 0" class="mb-6 rounded-lg border border-gray-200 bg-white">
          <p class="p-5 text-center">There is no currencies in this account.</p>
        </div>
      </div>
    </main>
  </layout>
</template>

<script>
import Layout from '@/Shared/Layout';
import PrettyButton from '@/Shared/Form/PrettyButton';
import PrettySpan from '@/Shared/Form/PrettySpan';
import TextInput from '@/Shared/Form/TextInput';
import Errors from '@/Shared/Form/Errors';

export default {
  components: {
    Layout,
    PrettyButton,
    PrettySpan,
    TextInput,
    Errors,
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
      localCurrencies: [],
      form: {
        name: '',
        errors: [],
      },
    };
  },

  mounted() {
    this.localCurrencies = this.data.currencies;
  },

  methods: {
    update(currency) {
      axios
        .put(currency.url.update, this.form)
        .then((response) => {
          this.flash('The currency has been updated', 'success');
          this.localCurrencies[this.localCurrencies.findIndex((x) => x.id === currency.id)].active = !currency.active;
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    enableAll() {
      axios
        .post(this.data.url.enable_all)
        .then((response) => {
          this.flash('The currencies have been updated', 'success');
          this.localCurrencies.forEach((entry) => {
            entry.active = true;
          });
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },

    disableAll() {
      axios
        .delete(this.data.url.disable_all)
        .then((response) => {
          this.flash('The currencies have been updated', 'success');
          this.localCurrencies.forEach((entry) => {
            entry.active = false;
          });
        })
        .catch((error) => {
          this.form.errors = error.response.data;
        });
    },
  },
};
</script>
