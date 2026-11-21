// Run: node --test --experimental-strip-types tests/signIn.test.ts
import assert from "node:assert/strict";
import { test } from "node:test";
import { signInDestination, validateSignIn } from "../src/lib/signIn.ts";

test("a requested path inside the role's own portal is kept, with its query", () => {
  assert.equal(signInDestination("/student", "/student/requirements"), "/student/requirements");
  assert.equal(
    signInDestination("/coordinator", "/coordinator/approvals?page=2"),
    "/coordinator/approvals?page=2",
  );
  assert.equal(signInDestination("/dean", "/dean"), "/dean");
});

test("other portals, external and malformed targets fall back to the role home", () => {
  const home = "/student";
  for (const target of [
    undefined,
    "",
    "/coordinator/approvals",
    "/studentx",
    "https://evil.example/student",
    "//evil.example/student",
    "/\\evil.example",
    "javascript:alert(1)",
    "student/requirements",
    "/student/../coordinator",
  ])
    assert.equal(signInDestination(home, target), home, String(target));
});

test("identifier accepts a six-digit Student ID or an email, like LoginRequest", () => {
  assert.deepEqual(validateSignIn({ identifier: "150006", password: "x" }), {});
  assert.deepEqual(validateSignIn({ identifier: " jm@example.com ", password: "x" }), {});
  assert.match(validateSignIn({ identifier: "", password: "x" }).identifier ?? "", /Enter your/);
  assert.match(
    validateSignIn({ identifier: "12345", password: "x" }).identifier ?? "",
    /six-digit/,
  );
  assert.match(
    validateSignIn({ identifier: "not-an-email", password: "x" }).identifier ?? "",
    /six-digit/,
  );
  assert.match(validateSignIn({ identifier: "150006", password: "" }).password ?? "", /password/);
});

test("new password rules match the server: 12+ characters, mixed case, number, symbol, confirmed", async () => {
  const { validateNewPassword, forgotPasswordSchema } = await import("../src/lib/signIn.ts");
  assert.deepEqual(validateNewPassword("Reset-Passw0rd-2026!", "Reset-Passw0rd-2026!"), {});
  assert.match(validateNewPassword("short", "short").password ?? "", /at least 12 characters/);
  assert.match(
    validateNewPassword("alllowercase123!", "alllowercase123!").password ?? "",
    /uppercase/,
  );
  assert.match(
    validateNewPassword("NoSymbolsHere123", "NoSymbolsHere123").password ?? "",
    /symbol/,
  );
  assert.match(
    validateNewPassword("Reset-Passw0rd-2026!", "different").confirmation ?? "",
    /do not match/,
  );
  assert.equal(forgotPasswordSchema.safeParse({ email: " jm@example.com " }).success, true);
  assert.equal(forgotPasswordSchema.safeParse({ email: "not-an-email" }).success, false);
});
