/** @type {import("prettier").Config} */
export default {
  semi: true,
  singleQuote: true,
  tabWidth: 2,
  trailingComma: 'es5',
  printWidth: 80,
  useTabs: false,
  quoteProps: 'as-needed',
  bracketSpacing: true,
  bracketSameLine: false,
  arrowParens: 'avoid',
  endOfLine: 'lf',
  embeddedLanguageFormatting: 'auto',
  htmlWhitespaceSensitivity: 'css',
  insertPragma: false,
  proseWrap: 'preserve',
  requirePragma: false,
  vueIndentScriptAndStyle: false,
  overrides: [
    {
      files: '*.json',
      options: {
        printWidth: 120,
      },
    },
    {
      files: '*.md',
      options: {
        printWidth: 100,
        proseWrap: 'always',
      },
    },
  ],
};