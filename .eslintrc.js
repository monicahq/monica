module.exports = {
  'env': {
    'browser': true,
    'es6': true
  },
  'extends': [
    'plugin:vue/recommended'
  ],
  'parserOptions': {
    'ecmaVersion': 12,
    'sourceType': 'module'
  },
  'plugins': [
    'vue',
  ],
  'rules': {
    'array-bracket-spacing': [
      'error',
      'never'
    ],
    'indent': [
      'error',
      2
    ],
    'linebreak-style': [
      'error',
      'unix'
    ],
    'no-trailing-spaces': [
      'error',
      {
        'ignoreComments': true,
        'skipBlankLines': true
      }
    ],
    'quotes': [
      'error',
      'single'
    ],
    'semi': [
      'error',
      'always'
    ],
    'semi-spacing': [
      'error',
      {
        'after': true,
        'before': false
      }
    ],
    'semi-style': [
      'error',
      'last'
    ],

    // strongly recommended
    'vue/component-name-in-template-casing': [
      'error',
      'kebab-case'
    ],
    'vue/component-tags-order': [
      'warn',
      {
        'order': [['style', 'template'], 'script']
      }
    ],
    'vue/html-end-tags': 'error',
    'vue/html-self-closing': [
      'error',
      {
        'html': {
          'normal': 'always',
          'void': 'never'
        }
      }
    ],
    'vue/no-v-model-argument': 0,
    'vue/no-v-html': 0,
    'vue/max-attributes-per-line': [
      // https://vuejs.org/v2/style-guide/#Multi-attribute-elements-strongly-recommended
      'error',
      {
        'singleline': 5,
        'multiline': {
          'max': 5,
          'allowFirstLine': true
        }
      }
    ],
    'vue/singleline-html-element-content-newline': ['error', {
      'ignoreWhenNoAttributes': true,
      'ignoreWhenEmpty': true,
      'ignores': ['pre', 'textarea', 'inertia-link', 'a', 'p', 'li']
    }]
  }
};
