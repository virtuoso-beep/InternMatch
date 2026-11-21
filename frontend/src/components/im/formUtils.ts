import { ApiError } from "@/lib/api";

export const inputClass =
  "w-full rounded-lg border border-input bg-card px-3 py-2 text-sm text-foreground shadow-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-brand focus:ring-2 focus:ring-brand/20 disabled:opacity-60 aria-[invalid=true]:border-brand aria-[invalid=true]:ring-2 aria-[invalid=true]:ring-brand/20";

/** A server error split into per-field messages (Laravel 422) and a general message. */
export type FormError = { message: string; fields: Record<string, string[]> };

export function toFormError(cause: unknown, fallback: string): FormError {
  if (cause instanceof ApiError) return { message: cause.message, fields: cause.errors ?? {} };
  return { message: cause instanceof Error ? cause.message : fallback, fields: {} };
}
