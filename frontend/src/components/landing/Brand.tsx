import { cx } from "@/components/im/ui";

/** UM Tagum College seal (small optimized copies of the original) with the InternMatch wordmark. */
export function Seal({ className }: { className?: string | undefined }) {
  return (
    <picture>
      <source
        type="image/webp"
        srcSet="/images/brand/umtc-seal-64.webp 1x, /images/brand/umtc-seal-128.webp 2x"
      />
      <img
        src="/images/brand/umtc-seal-64.png"
        srcSet="/images/brand/umtc-seal-64.png 1x, /images/brand/umtc-seal-128.png 2x"
        alt="UM Tagum College seal"
        width={64}
        height={64}
        className={cx("shrink-0", className ?? "size-10")}
      />
    </picture>
  );
}

export function Wordmark({ tone }: { tone: "light" | "dark" }) {
  return (
    <span className="leading-none">
      <span className="block text-lg font-bold tracking-tight">
        Intern<span className={tone === "light" ? "text-warn-light" : "text-brand"}>Match</span>
      </span>
      <span
        className={cx(
          "mt-1 block text-eyebrow font-semibold uppercase",
          tone === "light" ? "text-header-foreground/75" : "text-muted-foreground",
        )}
      >
        UM Tagum College
      </span>
    </span>
  );
}
