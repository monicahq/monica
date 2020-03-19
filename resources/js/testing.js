/**
 * Add cy-name and cy-items directives.
 * These are only active on local or testing environment.
 */

function testingDirective(el, binding, vnode) {
  if (window.Laravel.env != 'production') {
    var value = '';
    try {
      value = function(expr) {
        return eval(expr);
      }.call(vnode.context, ' with(this) { ' + binding.expression + ' } ');
    } catch (e) {
      value = binding.value;
    }
    el.setAttribute(binding.name, value.toString());
  }
}

Vue.directive('cy-items', testingDirective);
Vue.directive('cy-name', testingDirective);
