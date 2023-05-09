import emitter from 'tiny-emitter/instance';

/**
 * Flash a message.
 *
 * @param {string} message
 * @param {string} level
 */
export const flash = (message, level = 'success') => {
  emitter.emit('flash', { message, level });
};

export const isDark = () => {
  return (
    localStorage.theme === 'dark' ||
    (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
  );
};

export default {
  flash,
  isDark,
  $on: (...args) => emitter.on(...args),
  $once: (...args) => emitter.once(...args),
  $off: (...args) => emitter.off(...args),
};
