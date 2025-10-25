try {
  const pkg = require('rollup-plugin-visualizer');
  console.log('keys:', Object.keys(pkg));
  console.log('has default:', !!pkg.default);
  console.log('has visualizer:', !!pkg.visualizer);
  console.log('default type:', typeof pkg.default);
  console.log('visualizer type:', typeof pkg.visualizer);
  console.log('pkg:', pkg && pkg.default ? 'uses default' : (pkg.visualizer ? 'uses visualizer' : 'unknown'));
} catch (e) {
  console.error('require error:', e && e.message ? e.message : e);
}
