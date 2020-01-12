export default [
  {
    path: '/',
    redirect: '/users'
  },
  {
    path: '/users',
    name: 'users',
    component: require('./components/admin/users/UsersList').default
  },
  {
    path: '/user/:id',
    name: 'user',
    component: require('./components/admin/users/User').default,
    props: true
  },
  {
    path: '/config',
    name: 'config',
    component: require('./components/admin/configs/ConfigsList').default
  },
];
