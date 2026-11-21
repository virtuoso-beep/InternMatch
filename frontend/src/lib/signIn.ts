import { z } from "zod";

/**
 * Client-side rules for the sign-in form. They mirror App\Http\Requests\Auth\LoginRequest exactly:
 * a six-digit Student ID or a valid email (max 255), and a password (max 4096). The server enforces
 * the same rules; this only gives faster, inline feedback.
 */
export const signInSchema = z.object({
  identifier: z
    .string()
    .trim()
    .min(1, "Enter your 6-digit Student ID or email address.")
    .max(255, "That is too long for a Student ID or email address.")
    .refine(
      (value) => /^[0-9]{6}$/.test(value) || z.email().safeParse(value).success,
      "Enter a six-digit Student ID or a valid email address.",
    ),
  password: z.string().min(1, "Enter your password.").max(4096, "That password is too long."),
});

export type SignInValues = z.infer<typeof signInSchema>;
export type SignInField = keyof SignInValues;

export function validateSignIn(values: SignInValues): Partial<Record<SignInField, string>> {
  const result = signInSchema.safeParse(values);
  if (result.success) return {};
  const errors: Partial<Record<SignInField, string>> = {};
  for (const issue of result.error.issues) {
    const field = issue.path[0] as SignInField;
    errors[field] ??= issue.message;
  }
  return errors;
}

/**
 * Where to go after signing in, given the role's home path (ROLES[role].base). A requested path is honoured only when it is a same-origin path inside the
 * signed-in role's own portal; anything else (other roles, external or protocol-relative URLs) falls back
 * to the role's home page. Admins always continue to the management panel, handled by the caller.
 */
export function signInDestination(home: string, requested: string | undefined): string {
  if (
    !requested ||
    !requested.startsWith("/") ||
    requested.startsWith("//") ||
    requested.includes("\\")
  )
    return home;
  try {
    const url = new URL(requested, "http://internmatch.invalid");
    if (url.origin !== "http://internmatch.invalid") return home;
    const path = url.pathname;
    if (path !== home && !path.startsWith(`${home}/`)) return home;
    return `${path}${url.search}${url.hash}`;
  } catch {
    return home;
  }
}

/** Forgot password: an email address (the reset link is sent by email). */
export const forgotPasswordSchema = z.object({
  email: z
    .string()
    .trim()
    .min(1, "Enter your email address.")
    .max(255)
    .pipe(z.email("Enter a valid email address.")),
});

/** The password rules shared with activation and PasswordResetController (min 12, mixed case, number, symbol). */
export const PASSWORD_RULES = [
  { id: "length", label: "At least 12 characters", test: (p: string) => p.length >= 12 },
  { id: "upper", label: "An uppercase letter", test: (p: string) => /[A-Z]/.test(p) },
  { id: "lower", label: "A lowercase letter", test: (p: string) => /[a-z]/.test(p) },
  { id: "number", label: "A number", test: (p: string) => /[0-9]/.test(p) },
  { id: "symbol", label: "A symbol", test: (p: string) => /[^A-Za-z0-9]/.test(p) },
] as const;

export function validateNewPassword(
  password: string,
  confirmation: string,
): Partial<Record<"password" | "confirmation", string>> {
  const errors: Partial<Record<"password" | "confirmation", string>> = {};
  const failed = PASSWORD_RULES.filter((rule) => !rule.test(password));
  if (!password) errors.password = "Enter a new password.";
  else if (failed.length)
    errors.password = `Missing: ${failed.map((r) => r.label.toLowerCase()).join(", ")}.`;
  if (!confirmation) errors.confirmation = "Enter the new password again.";
  else if (confirmation !== password) errors.confirmation = "The passwords do not match.";
  return errors;
}
