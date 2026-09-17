import type { RoleKey } from "./internmatch";

export type SessionUser = {
  id: number;
  name: string;
  email: string;
  role: RoleKey;
  permissions: string[];
};

export class ApiError extends Error {
  constructor(public status: number, message: string) {
    super(message);
  }
}

const base = "/api/v1";

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  if (init.body) headers.set("Content-Type", "application/json");
  const response = await fetch(`${base}${path}`, { ...init, headers, credentials: "same-origin", cache: "no-store" });
  if (response.status === 204) return undefined as T;
  const json = await response.json().catch(() => null);
  if (!response.ok) {
    const message = response.status === 419
      ? "Your session expired. Please try again."
      : response.status === 429
        ? "Too many attempts. Please wait a minute and try again."
        : response.status >= 500
          ? "The service is unavailable. Please try again shortly."
          : json?.message ?? "The request could not be completed.";
    throw new ApiError(response.status, message);
  }
  if (json === null) throw new ApiError(502, "The API returned an unexpected response.");
  return json as T;
}

export async function mutate<T>(path: string, body?: unknown, method = "POST"): Promise<T> {
  const { token } = await api<{ token: string }>("/csrf");
  return api<T>(path, {
    method,
    headers: { "X-CSRF-TOKEN": token },
    ...(body === undefined ? {} : { body: JSON.stringify(body) }),
  });
}

export async function currentUser(): Promise<SessionUser> {
  return (await api<{ data: SessionUser }>("/session")).data;
}

export async function login(email: string, password: string, remember: boolean): Promise<SessionUser> {
  return (await mutate<{ data: SessionUser }>("/login", { email, password, remember })).data;
}

export async function logout(): Promise<void> {
  await mutate<void>("/logout");
}
