export type ValidationErrors = Record<string, string[]>

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors: ValidationErrors = {},
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const response = await fetch(path, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body !== undefined && { 'Content-Type': 'application/json' }),
    },
    body: body !== undefined ? JSON.stringify(body) : undefined,
  })

  if (!response.ok) {
    const data = await response.json().catch(() => ({}))

    throw new ApiError(
      data.message ?? `Request to ${path} failed (${response.status})`,
      response.status,
      data.errors ?? {},
    )
  }

  // 204 No Content has no body to parse (e.g. after a delete).
  if (response.status === 204) {
    return undefined as T
  }

  return response.json()
}

export function getJson<T>(path: string): Promise<T> {
  return request<T>('GET', path)
}

export function postJson<T>(path: string, body: unknown): Promise<T> {
  return request<T>('POST', path, body)
}

export function deleteJson(path: string): Promise<void> {
  return request<void>('DELETE', path)
}

export function patchJson<T>(path: string, body: unknown): Promise<T> {
  return request<T>('PATCH', path, body)
}
