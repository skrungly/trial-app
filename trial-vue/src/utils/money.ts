const gbp = new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' })

// Prices are handled in whole pence so totals don't pick up floating-point errors.
export function toPence(price: string | number): number {
  return Math.round(Number(price) * 100)
}

export function formatGBP(pence: number): string {
  return gbp.format(pence / 100)
}
