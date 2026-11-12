export async function getJson<T>(path: string): Promise<T> {
  const response = await fetch(path, {
    headers: { Accept: 'application/json' },
  })

  if (!response.ok) {
    throw new Error(`Request to ${path} failed (${response.status})`)
  }

  return response.json()
}
