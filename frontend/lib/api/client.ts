import type { ApiResponse, ApiFailure } from '@/types/api';

export class ApiClientError extends Error {
  status: number;
  code: string;
  fields?: Record<string, string>;

  constructor(status: number, failure: ApiFailure) {
    super(failure.error.message);
    this.status = status;
    this.code = failure.error.code;
    this.fields = failure.error.fields;
  }
}

export async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(path, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', ...(options.headers ?? {}) },
    ...options,
  });
  const payload = (await response.json()) as ApiResponse<T> | ApiFailure;
  if (!response.ok || !payload.success) {
    throw new ApiClientError(response.status, payload as ApiFailure);
  }
  return (payload as ApiResponse<T>).data;
}
