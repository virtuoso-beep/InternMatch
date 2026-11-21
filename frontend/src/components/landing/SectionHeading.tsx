import type { ReactNode } from "react";
import { cx } from "@/components/im/ui";
import { Reveal } from "./motion";

/** Section number and label above a serif heading, with a fine gold rule. */
export function Eyebrow({
  number,
  label,
  tone = "default",
}: {
  number: string;
  label: string;
  tone?: "default" | "inverse";
}) {
  return (
    <p
      className={cx(
        "flex items-center gap-4 text-eyebrow font-semibold uppercase",
        tone === "inverse" ? "text-warn-light" : "text-warn-ink",
      )}
    >
      <span>{number}</span>
      <span aria-hidden="true" className="h-px w-8 bg-current opacity-60" />
      <span className={tone === "inverse" ? "text-header-foreground/80" : "text-muted-foreground"}>
        {label}
      </span>
    </p>
  );
}

export function SectionHeading({
  id,
  number,
  label,
  title,
  lead,
  tone = "default",
  className,
}: {
  id: string;
  number: string;
  label: string;
  title: ReactNode;
  lead?: ReactNode;
  tone?: "default" | "inverse";
  className?: string | undefined;
}) {
  return (
    <Reveal className={className}>
      <Eyebrow number={number} label={label} tone={tone} />
      <h2 id={id} className="mt-6 max-w-3xl font-serif text-headline font-medium text-balance">
        {title}
      </h2>
      {lead && (
        <p
          className={cx(
            "mt-6 max-w-prose text-lg leading-relaxed",
            tone === "inverse" ? "text-header-foreground/85" : "text-muted-foreground",
          )}
        >
          {lead}
        </p>
      )}
    </Reveal>
  );
}
