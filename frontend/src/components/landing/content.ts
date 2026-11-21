/**
 * Landing page copy and photo slots.
 *
 * Photos live in public/images/campus/ as <file>-<width>.<ext>. A slot without `file` renders a sized
 * placeholder labelled "Photo: <need>", so adding a photo later causes no layout shift. For a new photo,
 * export each width in `widths` as WebP and JPEG (and AVIF when `avif` is true), set `file`, and
 * optionally `blur` (a tiny data URL shown while the image loads).
 *
 * TODO(verify): the current photos were supplied by the project team from public UM Tagum College posts
 * (several carry a "collegium" watermark). Confirm permission and the credit line before publishing, and
 * replace them with full-resolution originals when available (these are about 550 to 680 px wide).
 */

export type PhotoRatio = "4/5" | "3/2" | "16/9";

export type PhotoSlot = {
  /** Base file name in /images/campus, without width or extension. */
  file?: string;
  /** Widths exported for this photo; defaults to PHOTO_WIDTHS. */
  widths?: readonly number[];
  /** True when AVIF files were exported as well as WebP and JPEG. */
  avif?: boolean;
  /** What the photo must show; used for the placeholder label and the missing-photo list. */
  need: string;
  /** Alt text describing the photo. */
  alt: string;
  ratio: PhotoRatio;
  /** Tiny blurred preview (data URL) shown while the image loads. */
  blur?: string;
};

export const PHOTO_WIDTHS = [640, 1280, 1920] as const;

export const PHOTO_CREDIT = "Campus photos: The Collegium"; // TODO(verify): confirm credit and permission.

export const photoWidths = (photo: PhotoSlot): readonly number[] => photo.widths ?? PHOTO_WIDTHS;

export const photoUrl = (photo: PhotoSlot, width: number, ext: string) =>
  `/images/campus/${photo.file}-${width}.${ext}`;

export const photoSrcSet = (photo: PhotoSlot, ext: string) =>
  photoWidths(photo)
    .map((w) => `${photoUrl(photo, w, ext)} ${w}w`)
    .join(", ");

/** The middle (or only) width, used for the plain src and the Open Graph image. */
export const photoDefaultWidth = (photo: PhotoSlot): number => {
  const widths = photoWidths(photo);
  return widths[Math.floor(widths.length / 2)] ?? widths[0] ?? 1280;
};

export const PHOTOS = {
  hero: {
    file: "umtc-sign",
    widths: [678],
    need: "Campus façade or main gate, wide",
    alt: "The UMTC sign and the college seal on the UM Tagum College campus grounds",
    ratio: "16/9",
    blur: "data:image/webp;base64,UklGRqYCAABXRUJQVlA4WAoAAAAgAAAAFwAADwAASUNDUMgBAAAAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADZWUDgguAAAAHAEAJ0BKhgAEAA+0VSjS6gkoyGwCAEAGglkALsgNmDADSUcSaJ3vSwPnvaAAP7p1813uW2BnbAgLm8J5nEXq5S29G4OGKQywiIv/nE46kHRpon/CLDdRESYciarpIFJzVQqLs4CWeLiLBoz+OtIi4EpPCOok2Moa+WHoLN+ahPKKObP4JsFzxVc+gkX2JsdyRZFyOH2x5oDXKl3mw3Sd/I1+4huX6eqd4YlUnM27Zj7MHb/HUDTQAA=",
  },
  about: {
    file: "gymnasium-students",
    widths: [547],
    need: "Students on campus",
    alt: "Students gathered in front of the Pedro B. San Jose Gymnasium at UM Tagum College",
    ratio: "4/5",
    blur: "data:image/webp;base64,UklGRqwCAABXRUJQVlA4WAoAAAAgAAAAFwAADwAASUNDUMgBAAAAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADZWUDggvgAAAHAEAJ0BKhgAEAA+0VSjS6gkoyGwCAEAGglAE6ZQBegmcgpecHv4zk5WP2SAAN5M4Q2fcYtXMY3syI+/JKrOj9m/RAanlj3WfeR6ldXGjhbGWGolyneu+TV4o4Ox+kwc8J3B1nAPdiJQx5B1ESlSf2lqTUxrTe4t+Y/eJNovUbUhHqK3K2busvO5ZQR3QcvjQhpNAVJEMNk/QdWsRfwlUrhKYcrqbrfs5x0htspZkUcRj0srm3/u6DDBuKIAAAA=",
  },
  practicum: {
    file: "internship-culmination",
    widths: [547],
    need: "Practicum activity",
    alt: "Students at the Internship Culmination and HTE Recognition ceremony at UM Tagum College",
    ratio: "4/5",
    blur: "data:image/webp;base64,UklGRqYCAABXRUJQVlA4WAoAAAAgAAAAFwAADwAASUNDUMgBAAAAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADZWUDgguAAAAJAEAJ0BKhgAEAA+0VSjS6gkoyGwCAEAGglkAJ0BjKjgkMNBpJBOsFEsuCECAAD+7vI6mcA2+vvwdnQ88U1Cgu7sk6qp3T6gTASZx5aPg819Fmn8xb3nBUcdbv3h8h4hk7du427/+ROOViF6sSmMWr5cv6RjmzE3emmek76WgsCEeJPcQHVjMzg5j0Cm9RdTtvQGyNpTXz32ZNU6mb4GfYALBqa/bUwH/iAW/G98dxly5CaBJFA0gAA=",
  },
  mosaic: [
    {
      file: "culmination-ceremony",
      widths: [678],
      need: "Practicum ceremony",
      alt: "The Internship Culmination and HTE Recognition Ceremony inside the UM Tagum College gymnasium",
      ratio: "3/2",
      blur: "data:image/webp;base64,UklGRqYCAABXRUJQVlA4WAoAAAAgAAAAFwAADwAASUNDUMgBAAAAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADZWUDgguAAAALAEAJ0BKhgAEAA+0VSjS6gkoyGwCAEAGgloAJ0fwYn+A2hxBvrubY3haYWSnKAA/qncgD0yZ+YizNiPFLsPSn1QTQe5EETYfS7VZCAdycn0glODrD2uB523LMMsGnGFpd3MDt2uTkqaRTdp9ROKTl3Y8blyCyVSTv2dytIU+9KUu0fj5WwLxx2cuVF1ku+xeJqrt1oGP7axUwUtnns/XtHUq6JEYQIk2Cpqa/HY8/FuaQruZ30AAAA=",
    },
    {
      file: "intern-certificate",
      widths: [678],
      need: "Practicum completion",
      alt: "A student intern holding a certificate at an OJT culmination program",
      ratio: "3/2",
      blur: "data:image/webp;base64,UklGRqgCAABXRUJQVlA4WAoAAAAgAAAAFwAADwAASUNDUMgBAAAAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADZWUDggugAAAPAEAJ0BKhgAEAA+0VSjS6gkoyGwCAEAGglkAJ0ygy6XSO3A8JZ4HZvlqWxr46GfoAD+7ATW6nv76zpq7FSlSqyv1uG7KcRFMyxKa49dIwGZ/JA/KiBSFehocps+A18md7ajTukd5nlm+wHvmUeeeOMWdLsdIRdH65ib2QN3WeBwq9ozDvyxsqub43DDbR2YYBiU3zAcG5aEgDeEss9zQmyapNJFgWr1gYkf4d4fsUhQbKgqGfSt0RhAAA==",
    },
    {
      file: "faculty-greeting",
      widths: [678],
      need: "Faculty and students",
      alt: "A faculty member greeting students during a UM Tagum College ceremony",
      ratio: "3/2",
      blur: "data:image/webp;base64,UklGRrgCAABXRUJQVlA4WAoAAAAgAAAAFwAADwAASUNDUMgBAAAAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADZWUDggygAAAHAEAJ0BKhgAEAA+0VSjS6gkoyGwCAEAGglmAMb1jCa179JSqplXctDZIbgAAOAtbED5CowNRSBFXuoF+2cAnYX4Dc9Uy7pxqd/f6Q3duIVNSeo0ZuxvLy17HgzqXCjcbmEnpTpqZjDX74pa8e7txKcm2bj82nABVyLpt6Uuj5GyweoOvETBFtnz4K/1AHPZwu3lHznFHU1Iz2fZWiDaEUFBxI9di68Qgo+BUCLZ7rq2+Gx1EsQ9eQdy9SuYA6qcYDKQT86CLMQIAAA=",
    },
    {
      file: "gymnasium-venue",
      widths: [678],
      need: "Campus venue",
      alt: "The UM Tagum College gymnasium prepared for a ceremony",
      ratio: "3/2",
      blur: "data:image/webp;base64,UklGRpQCAABXRUJQVlA4WAoAAAAgAAAAFwAADwAASUNDUMgBAAAAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADZWUDggpgAAAFAEAJ0BKhgAEAA+0VSjS6gkoyGwCAEAGgljALEMXkn+Avz8Oteu5aOq4AAA/h2Nce2yk+4baniDd3tIwMb1l+T6LoBzQbUo9w+VqS/LN87JEtAtZzLepUEEB60dG5pS50UdclZb4jDVf0qCoJOsyt+FKdPaJ8xTiSnHklwpavumQIIBlSuLDryoGKh26fadgtSW8ZkwaPKP0z/0VygSQsvvrzTiAAA=",
    },
  ],
} satisfies {
  hero: PhotoSlot;
  about: PhotoSlot;
  practicum: PhotoSlot;
  mosaic: PhotoSlot[];
};

export const NAV = [
  { href: "#about", label: "About" },
  { href: "#how-it-works", label: "How it works" },
  { href: "#roles", label: "Roles" },
] as const;

export const STEPS = [
  {
    title: "Profile",
    body: "Students record their competencies and submit pre-deployment requirements. Coordinators confirm academic eligibility.",
  },
  {
    title: "AI matching",
    body: "Each student's competencies are compared with the tasks of every eligible opportunity, and straight-line distance to the host is calculated.",
  },
  {
    title: "Placement",
    body: "The practicum coordinator reviews the ranked suggestions, checks capacity and agreements, then approves, changes or rejects each placement.",
  },
  {
    title: "Monitoring",
    body: "Hours, journals, requirements and supervisor evaluations are tracked through to completion, with early flags when a student falls behind.",
  },
] as const;

export const ROLE_GROUPS = [
  {
    key: "student",
    title: "Students",
    items: [
      "A competency profile and requirement checklist",
      "Ranked internship suggestions with reasons",
      "Time logs, journals and progress in one place",
    ],
  },
  {
    key: "coordinator",
    title: "Practicum Coordinators",
    items: [
      "Requirement review and academic eligibility",
      "Placement proposals, approval and reassignment",
      "Monitoring of hours, journals and evaluations",
    ],
  },
  {
    key: "supervisor",
    title: "Host Supervisors",
    items: [
      "Establishment profile and openings",
      "Verification of interns' hours and journals",
      "Structured end-of-practicum evaluations",
    ],
  },
  {
    key: "dean",
    title: "The Dean",
    items: [
      "Read-only view of placement and progress",
      "Program and institutional reports",
      "Report approval",
    ],
  },
  {
    key: "admin",
    title: "The Administrator",
    items: [
      "Accounts, roles and access",
      "Academic terms and system settings",
      "Audit trail and backups",
    ],
  },
] as const;

export const COLLEGE = {
  name: "University of Mindanao Tagum College",
  short: "UM Tagum College",
  // TODO(verify): confirm the official campus address before publishing.
  address: "Arellano Street, Tagum City, Davao del Norte, Philippines",
} as const;
