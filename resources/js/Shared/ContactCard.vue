<template>
  <div class="relative inline" :style="'top: ' + top">
    <a-popover placement="bottomLeft">
      <!-- popup that appears on mouse over -->
      <template #content>
        <div class="flex">
          <!-- avatar -->
          <div class="mr-2">
            <div v-if="contact.avatar.type === 'svg'" class="h-16 w-16 rounded-full" v-html="contact.avatar.content" />
            <img v-else class="h-16 w-16 rounded-full" :src="contact.avatar.content" alt="avatar" />
          </div>

          <div>
            <p class="mb-2 text-lg font-semibold">{{ contact.name }}</p>

            <!-- birthdate -->
            <p class="flex items-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="mr-1 h-4 w-4 text-gray-400">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
              </svg>

              <span v-if="contact.age">{{ contact.age }}</span>
              <span v-else class="text-sm italic text-gray-600">Unknown</span>
            </p>

            <!-- groups -->
            <div v-if="contact.groups.length > 0" class="mt-2 flex items-start">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="mr-2 h-4 w-4 text-gray-400">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
              </svg>

              <ul>
                <li v-for="group in contact.groups" :key="group.id" class="group-list-item">
                  <inertia-link class="text-blue-500 hover:underline">
                    {{ group.name }}
                  </inertia-link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </template>
      <template v-if="showName" #title>
        <span>{{ contact.name }}</span>
      </template>

      <!-- default state -->
      <div class="inline-flex items-center text-sm">
        <!-- avatar -->
        <div class="img relative">
          <inertia-link :href="contact.url">
            <div v-if="contact.avatar.type === 'svg'" :class="avatarClasses" v-html="contact.avatar.content" />
            <img v-else :class="avatarClasses" :src="contact.avatar.content" alt="avatar" />
          </inertia-link>
        </div>

        <!-- name -->
        <a v-if="showName" class="colored-link" href="">{{ contact.name }}</a>
      </div>
    </a-popover>
  </div>
</template>

<script>
export default {
  components: {},

  props: {
    top: {
      type: String,
      default: '0px',
    },
    contact: {
      type: Object,
      default: null,
    },
    avatarClasses: {
      type: String,
      default: '',
    },
    displayName: {
      type: Boolean,
      default: true,
    },
  },
};
</script>

<style lang="scss" scoped></style>
