// Vorlage: in den Repo-Root kopieren.
// Erzwingt die Schichtung aus harness/architecture.md.
module.exports = {
  forbidden: [
    {
      name: 'domain-stays-pure',
      comment: 'Domain-Logik darf kein Vue/UI/API/Store/Composable importieren.',
      severity: 'error',
      from: { path: '^src/domain' },
      to: { path: '^(src/components|src/composables|src/api|src/stores)|(^|/)node_modules/vue' },
    },
    {
      name: 'components-not-to-api',
      comment: 'Komponenten sprechen die API nur ueber Composables/Stores an.',
      severity: 'error',
      from: { path: '^src/components' },
      to: { path: '^src/api' },
    },
    {
      name: 'no-circular',
      comment: 'Keine Zyklen.',
      severity: 'error',
      from: {},
      to: { circular: true },
    },
  ],
  options: {
    doNotFollow: { path: 'node_modules' },
    tsConfig: { fileName: 'tsconfig.json' },
  },
}
