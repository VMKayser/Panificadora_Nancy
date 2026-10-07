module.exports = {
  root: true,
  env: { browser: true, es2020: true },
  extends: [
    'eslint:recommended',
    'plugin:react/recommended',
    'plugin:react/jsx-runtime',
    'plugin:react-hooks/recommended',
  ],
  ignorePatterns: ['dist', '.eslintrc.cjs'],
  parserOptions: { ecmaVersion: 'latest', sourceType: 'module' },
  settings: { react: { version: '18.2' } },
  plugins: ['react-refresh'],
  rules: {
    'react-refresh/only-export-components': [
      'warn',
      { allowConstantExport: true },
    ],
    // El proyecto no declara PropTypes (solo un componente lo hacía) y no usa
    // TypeScript: la regla solo generaba ruido, no detectaba errores reales.
    'react/prop-types': 'off',
    // Permite separar props con desestructuración para no pasarlas al DOM:
    // const { placement, ...props } = p
    'no-unused-vars': ['error', { ignoreRestSiblings: true }],
  },
  overrides: [
    {
      // Configuración y scripts que corren en Node, no en el navegador
      files: ['vite.config.js', 'scripts/**/*.{js,mjs,cjs}', '*.cjs', 'tmp_*.js'],
      env: { node: true, browser: false },
    },
  ],
}
