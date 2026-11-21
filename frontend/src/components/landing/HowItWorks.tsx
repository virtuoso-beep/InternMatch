import { Activity, ClipboardCheck, ScanSearch, UserRound, type LucideIcon } from "lucide-react";
import { CampusPhoto } from "./CampusPhoto";
import { PHOTOS, STEPS } from "./content";
import { Reveal } from "./motion";
import { SectionHeading } from "./SectionHeading";

const ICONS: LucideIcon[] = [UserRound, ScanSearch, ClipboardCheck, Activity];

/** Four numbered steps on a connected vertical line, beside a photo that stays in view on wide screens. */
export function HowItWorks() {
  return (
    <section
      id="how-it-works"
      aria-labelledby="how-title"
      className="scroll-mt-20 border-t border-border bg-card"
    >
      <div className="mx-auto grid max-w-7xl gap-16 px-4 py-24 sm:px-8 lg:grid-cols-12 lg:py-32">
        <div className="lg:col-span-5">
          <div className="lg:sticky lg:top-28">
            <SectionHeading
              id="how-title"
              number="02"
              label="How it works"
              title="From profile to placement, in four steps."
            />
            <Reveal
              index={1}
              className="mt-10 hidden overflow-hidden rounded-lg shadow-lg lg:block"
            >
              <CampusPhoto photo={PHOTOS.practicum} sizes="(min-width: 1024px) 35vw, 0px" />
            </Reveal>
          </div>
        </div>
        <ol className="relative lg:col-span-6 lg:col-start-7">
          <span aria-hidden="true" className="absolute top-2 bottom-2 left-6 w-px bg-border" />
          {STEPS.map((step, index) => {
            const Icon = ICONS[index] ?? Activity;
            return (
              <Reveal
                as="li"
                key={step.title}
                index={index}
                className="relative flex gap-8 pb-16 last:pb-0"
              >
                <span className="relative z-10 flex size-12 shrink-0 items-center justify-center rounded-full border border-border bg-card text-warn-ink shadow-sm">
                  <Icon aria-hidden="true" size={20} />
                </span>
                <div className="pt-2">
                  <p className="text-eyebrow font-semibold uppercase text-warn-ink">
                    Step {String(index + 1).padStart(2, "0")}
                  </p>
                  <h3 className="mt-2 font-serif text-title font-medium">{step.title}</h3>
                  <p className="mt-4 max-w-prose leading-relaxed text-muted-foreground">
                    {step.body}
                  </p>
                </div>
              </Reveal>
            );
          })}
        </ol>
      </div>
    </section>
  );
}
