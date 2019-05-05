<style lang="scss" scoped>
  .avatar-new {
    background-color: #fdb660;
  }
  .item-search-result {
    position: relative;
    background: transparent;

    a {
      color: inherit;
      text-decoration: none;
      vertical-align: middle;
      background: transparent;

      span {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;

        z-index: 1;
      }
    }

    a:hover {
      background: inherit;
      color: inherit;
    }

    .avatar {
      border-radius: 3px;
      display: inline-block;
      height: 36px;
      margin: 10px;
      width: 36px;
    }

    .avatar-initials {
      text-align: center;
      padding-top: 6px;
      font-size: 15px;
      color: #fff;
    }

    &:last-child {
      border-bottom: initial;
    }
  }

</style>

<template>
  <div v-if="item.id > 0" class="item-search-result" :data-contact="item.id" :data-name="item.name">
    <a :href="'/people/'+item.hash_id">
      <img v-if="item.information.avatar.has_avatar"
           :src="item.information.avatar.avatar_url"
           class="avatar"
      />
      <div v-else
           class="avatar avatar-initials"
           :style="'background-color: '+item.information.avatar.default_avatar_color"
      >
        {{ item.initials }}
      </div>
      {{ item.complete_name }}
      <span></span>
    </a>
  </div>
  <div v-else class="item-search-result">
    <a href="/people/add">
      <div class="avatar avatar-initials avatar-new">+</div>
      {{ $t('people.people_add_new') }}
      <span></span>
    </a>
  </div>
</template>

<script>
export default {
  props: {
    item: {
      type: Object,
      required: true,
      default: null,
    },
  }
};
</script>
