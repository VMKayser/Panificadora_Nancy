// Small number formatting utilities used across the app
export function formatCurrency(value, decimals = 2) {
  const n = Number(value);
  if (!Number.isFinite(n) || Number.isNaN(n)) return (0).toFixed(decimals);
  return n.toFixed(decimals);
}

export default { formatCurrency };
