/**
 * Pluralization form for every langage not following engl-ish like form.
 *
 * 'plurals' functions represent possible plural form transformations.
 * This return a list of lang/plural function to apply.
 *
 * @see https://github.com/laravel/framework/blob/master/src/Illuminate/Translation/MessageSelector.php
 */

function pluralA (choice, choicesLength) {
  return 0;
}
function pluralB (choice, choicesLength) {
  let number = Math.abs(choice);
  number = ((number == 0) || (number == 1)) ? 0 : 1;
  return Math.min(number, choicesLength - 1);
}
function pluralC (choice, choicesLength) {
  let number = Math.abs(choice);
  number = (number == 1) ? 0 : (((number >= 2) && (number <= 4)) ? 1 : 2);
  return Math.min(number, choicesLength - 1);
}
function pluralD (choice, choicesLength) {
  let number = Math.abs(choice);
  number = ((number % 10 == 1) && (number % 100 != 11)) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);
  return Math.min(number, choicesLength - 1);
}
function pluralE (choice, choicesLength) {
  let number = Math.abs(choice);
  number = (number == 0) ? 0 : ((number == 1) ? 1 : ((number == 2) ? 2 : (((number % 100 >= 3) && (number % 100 <= 10)) ? 3 : (((number % 100 >= 11) && (number % 100 <= 99)) ? 4 : 5))));
  return Math.min(number, choicesLength - 1);
}
function pluralF (choice, choicesLength) {
  let number = Math.abs(choice);
  number = (number == 1) ? 0 : ((number == 2) ? 1 : (number < 10 && number % 10 == 0) ? 2 : 3);
  return Math.min(number, choicesLength - 1);
}

export default {
  'ar': pluralE,
  'cs': pluralC,
  'fr': pluralB,
  'he': pluralF,
  'hr': pluralD,
  'id': pluralA,
  'ja': pluralA,
  'ru': pluralD,
  'tr': pluralA,
  'uk': pluralD,
  'vi': pluralA,
  'zh': pluralA,
  'zh-TW': pluralA,
};
