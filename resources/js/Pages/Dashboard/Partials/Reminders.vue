<style lang="scss" scoped>
.reminders {
  li:last-child {
    border-bottom: 0;
  }
}
</style>

<template>
  <div class="ba bg-white br2 box bc-gray">
    <h2 class="ma0 bb pa3 fw5 f5 bc-gray c-strong">Upcoming reminders</h2>
    <ul class="ma0 list pl0 reminders">
      <li class="bb bc-gray pv2 ph3 flex items-center" v-for="reminder in reminders" :key="reminder.id">
        <span class="mr3 tc c-gray f6">
          {{ reminder.month }}
          <span class="db">{{ reminder.day }}</span>
        </span>
        <img :src="reminder.contact.avatar" class="br-100 ba h2 w2 dib mr2 bc-gray" alt="avatar">
        <div>
          <span class="db mb1">{{ reminder.title }}</span>
          <span class="db f6 mb1"><inertia-link :href="reminder.contact.url">{{ reminder.contact.name }}</inertia-link></span>
        </div>
      </li>
    </ul>
  </div>
</template>

<script>

export default {
  props: {
    reminders: {
      type: Array,
      default: null,
    },
  },
};
</script>
