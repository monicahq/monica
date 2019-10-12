/**
 * Pluralization form for every langage not following engl-ish like form.
 * 
 * 'plurals' functions represent possible plural form transformations.
 * This return a list of lang/plural function to apply.
 * 
 * @see https://github.com/laravel/framework/blob/master/src/Illuminate/Translation/MessageSelector.php
 */

function pluralA (number, choicesLength) {
  return 0;
}
function pluralB (number, choicesLength) {
  number = Math.abs(number);
  number = ((number == 0) || (number == 1)) ? 0 : 1;
  return Math.min(number, choicesLength - 1);
}
function pluralC (number, choicesLength) {
  number = Math.abs(number);
  number = (number == 1) ? 0 : (((number >= 2) && (number <= 4)) ? 1 : 2);
  return Math.min(number, choicesLength - 1);
}
function pluralD (number, choicesLength) {
  number = Math.abs(number);
  number = ((number % 10 == 1) && (number % 100 != 11)) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);
  return Math.min(number, choicesLength - 1);
}
function pluralE (number, choicesLength) {
  number = Math.abs(number);
  number = (number == 0) ? 0 : ((number == 1) ? 1 : ((number == 2) ? 2 : (((number % 100 >= 3) && (number % 100 <= 10)) ? 3 : (((number % 100 >= 11) && (number % 100 <= 99)) ? 4 : 5))));
  return Math.min(number, choicesLength - 1);
}

export default {
  'ar': pluralE,
  'cs': pluralC,
  'fr': pluralB,
  'hr': pluralD,
  'ru': pluralD,
  'tr': pluralA,
  'zh': pluralA,
};
