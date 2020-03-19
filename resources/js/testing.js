function testingDirective(el, binding, vnode) {
  if (window.Laravel.env != 'production') {
    try {
    var value = function(expr) {
        return eval(expr);
      }.call(vnode.context, ' with(this) { ' + binding.expression + ' } ');

    el.setAttribute(binding.name, value.toString());
    } catch (e) {
      // ignore error
    }
  }
}

Vue.directive('cy-items', testingDirective);
Vue.directive('cy-name', testingDirective);
