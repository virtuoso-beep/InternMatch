/** Spec §17A-B activation states shown in import results. */
export const activationLabel = (state: string) =>
  ({
    link_sent: "Link sent",
    activated: "Activated",
    expired: "Expired",
    email_failed: "Email failed",
  })[state] ?? state;
