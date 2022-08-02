<template>
  <div class="relative border border-gray-300 bg-white sm:rounded-lg">
    <span class="absolute rounded border bg-blue-50 px-2 py-0 text-xs text-blue-500" style="top: -10px; left: 10px">
      entry
    </span>
    <div class="p-3 pt-5">
      <p>
        Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem
        aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo
        enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos
        qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet,
        consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam
        quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam,
        nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse
        quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?
      </p>
    </div>
    <div class="flex border-t border-gray-200 px-3 py-2">
      <div class="mr-3">
        <span class="relative mr-1 text-sm text-gray-500">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-location relative inline h-5 w-5 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </span>
        <div class="mb-2 mr-2 inline text-sm">Place Dupuis</div>
      </div>
      <div class="mr-3">
        <span class="relative mr-1 text-sm text-gray-500">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-location relative inline h-5 w-5 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </span>
        <div class="mb-2 mr-2 inline text-sm">8 F - Cloudly</div>
      </div>
      <div class="mr-3">
        <span class="mr-3 text-sm text-gray-500"> Labels </span>
        <ul class="mb-2 mr-2 inline text-sm">
          <li class="tag mr-2 inline">voyage</li>
          <li class="tag mr-2 inline">souvenir</li>
          <li class="tag mr-2 inline">amour</li>
        </ul>
      </div>
    </div>
    <div class="flex justify-between border-t border-gray-200 px-3 py-2 text-xs text-gray-600">
      <div>
        <div class="relative mr-3 inline">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="icon-date relative inline h-4 w-4 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Sept 21, 2020
        </div>
        <div class="inline">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="inline h-4 w-4 text-green-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
          Published online
        </div>
      </div>
      <div>
        <hover-menu />
      </div>
    </div>
  </div>
</template>

<script>
import HoverMenu from '@/Shared/HoverMenu';

export default {
  components: {
    HoverMenu,
  },
};
</script>

<style lang="scss" scoped>
.icon-date {
  top: -1px;
}
</style>
