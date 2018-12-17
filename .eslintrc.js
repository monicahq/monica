module.exports = {
    "env": {
        "browser": true,
        "es6": true
    },
    "extends": [
      "plugin:vue/recommended"
    ],
    "parserOptions": {
        "ecmaVersion": 2017,
        "sourceType": "module"
    },
    "rules": {
        "indent": [
            "error",
            4
        ],
        "linebreak-style": [
            "error",
            "unix"
        ],
        "quotes": [
            "error",
            "single"
        ],
        "semi": [
            "error",
            "always"
        ],
        "semi-spacing": [
          "error",
          {
              "after": true,
              "before": false
          }
        ],
        "semi-style": [
          "error",
          "last"
        ],
        "array-bracket-spacing": [
          "error",
          "never"
        ],
        "no-trailing-spaces": [
          "error",
          {
              "ignoreComments": true,
              "skipBlankLines": true
          }
        ],
        "vue/no-unused-components" : "off"
    }
};