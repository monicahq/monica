<script setup>
import { Link } from '@inertiajs/vue3';
import ChevronLeft from '@/Components/ChevronLeft.vue';
import ChevronRight from '@/Components/ChevronRight.vue';

defineProps({
  items: Object,
  withSummary: {
    type: Boolean,
    default: true,
  },
});

const commonClasses =
  'relative inline-flex items-center px-4 py-2 text-sm font-medium bg-white border border-gray-300 dark:border-gray-700 leading-5';
const linkClasses =
  'hover:text-gray-500 focus:outline-hidden focus:ring-3 ring-gray-300 dark:ring-gray-700 focus:border-blue-300 dark:focus:border-blue-700 active:bg-gray-100 dark:active:bg-gray-900 active:text-gray-700 dark:active:text-gray-300 transition ease-in-out duration-150';
</script>

<template>
  <nav
    v-if="items.currentPage !== 1 || items.lastPage > 1"
    role="navigation"
    :aria-label="$t('Pagination Navigation')"
    class="flex items-center justify-between">
    <div class="flex flex-1 justify-between sm:hidden">
      <span
        v-if="items.currentPage === 1"
        :class="[commonClasses, 'rounded-md', 'text-gray-500', 'dark:bg-gray-900', 'cursor-default']">
        <span v-html="$t('Previous')"></span>
      </span>
      <Link
        v-else
        :href="items.previousPageUrl"
        preserve-scroll
        :class="[commonClasses, linkClasses, 'rounded-md', 'text-gray-700', 'dark:text-gray-300', 'dark:bg-gray-900']">
        <span v-html="$t('Previous')"></span>
      </Link>

      <Link
        v-if="items.lastPage > 1"
        :href="items.nextPageUrl"
        preserve-scroll
        :class="[
          commonClasses,
          linkClasses,
          'ms-3',
          'rounded-md',
          'text-gray-700',
          'dark:text-gray-300',
          'dark:bg-gray-900',
        ]">
        <span v-html="$t('Next')"></span>
      </Link>
      <span
        v-else
        :class="[commonClasses, 'ms-3', 'rounded-md', 'text-gray-500', 'cursor-default', 'dark:bg-gray-900']">
        <span v-html="$t('Next')"></span>
      </span>
    </div>

    <div class="hidden sm:flex sm:flex-1 sm:flex-col sm:items-center sm:justify-between">
      <p v-if="withSummary" class="mb-2 text-xs leading-5 text-gray-700 dark:text-gray-300">
        <span v-if="items.firstItem">
          {{
            $t('Showing :first to :last of :total results', {
              first: items.firstItem,
              last: items.lastItem,
              total: items.total,
            })
          }}
        </span>
        <span v-else>
          {{
            $t('Showing :count of :total results', {
              count: items.data.length,
              total: items.total,
            })
          }}
        </span>
      </p>

      <div class="relative z-0 inline-flex rounded-md shadow-xs">
        <span
          v-if="items.currentPage === 1"
          :class="[
            commonClasses,
            'px-2',
            'rounded-s-md',
            'text-gray-500',
            'focus:z-10',
            'cursor-default',
            'dark:bg-gray-900',
          ]"
          :aria-label="$t('Previous')"
          aria-hidden="true"
          aria-disabled="true">
          <ChevronLeft />
        </span>

        <Link
          v-else
          :href="items.previousPageUrl"
          preserve-scroll
          rel="prev"
          :class="[
            commonClasses,
            linkClasses,
            'px-2',
            'rounded-s-md',
            'text-gray-500',
            'dark:bg-gray-900',
            'hover:text-gray-400',
            'dark:hover:text-gray-600',
            'focus:z-10',
          ]"
          :aria-label="$t('Previous')">
          <ChevronLeft />
        </Link>

        <template v-for="(link, id) in items.links" :key="id">
          <span
            v-if="link.url === null"
            aria-disabled="true"
            :class="[
              commonClasses,
              '-ms-px',
              'text-gray-700',
              'dark:text-gray-500',
              'dark:bg-gray-900',
              'cursor-default',
            ]"
            v-html="link.label">
          </span>
          <span
            v-else-if="link.active"
            aria-current="page"
            :class="[commonClasses, '-ms-px', 'text-gray-500', 'bg-gray-100', 'dark:bg-gray-800', 'cursor-default']"
            v-html="link.label">
          </span>
          <Link
            v-else
            :href="link.url"
            preserve-scroll
            :class="[
              commonClasses,
              linkClasses,
              '-ms-px',
              'text-gray-700',
              'dark:text-gray-300',
              'dark:bg-gray-900',
              'focus:z-10',
            ]"
            :aria-label="$t('Go to page :page', { page: link.label })">
            <span v-html="link.label"></span>
          </Link>
        </template>

        <Link
          v-if="items.currentPage < items.lastPage"
          :href="items.nextPageUrl"
          preserve-scroll
          rel="next"
          :class="[
            commonClasses,
            linkClasses,
            '-ms-px',
            'px-2',
            'rounded-e-md',
            'text-gray-500',
            'dark:bg-gray-900',
            'hover:text-gray-400',
            'dark:hover:text-gray-600',
            'focus:z-10',
          ]"
          :aria-label="$t('Next')">
          <ChevronRight />
        </Link>
        <span
          v-else
          :class="[
            commonClasses,
            linkClasses,
            '-ms-px',
            'px-2',
            'rounded-e-md',
            'text-gray-500',
            'dark:bg-gray-900',
            'focus:z-10',
            'cursor-default',
          ]"
          :aria-label="$t('Next')"
          aria-hidden="true"
          aria-disabled="true">
          <ChevronRight />
        </span>
      </div>
    </div>
  </nav>
</template>
