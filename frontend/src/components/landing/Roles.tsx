import {
  Building2,
  GraduationCap,
  Landmark,
  ShieldCheck,
  UsersRound,
  type LucideIcon,
} from "lucide-react";
import { ROLE_GROUPS } from "./content";
import { Reveal } from "./motion";
import { SectionHeading } from "./SectionHeading";

const ICONS: Record<(typeof ROLE_GROUPS)[number]["key"], LucideIcon> = {
  student: GraduationCap,
  coordinator: UsersRound,
  supervisor: Building2,
  dean: Landmark,
  admin: ShieldCheck,
};

/** An index of the five roles: one row each, separated by fine rules. */
export function Roles() {
  return (
    <section id="roles" aria-labelledby="roles-title" className="scroll-mt-20 bg-background">
      <div className="mx-auto max-w-7xl px-4 py-24 sm:px-8 lg:py-32">
        <SectionHeading
          id="roles-title"
          number="03"
          label="For every role"
          title="One system, five points of view."
          lead="Each person sees what their work needs, and nothing that belongs to someone else."
        />
        <ul className="mt-16 border-t border-foreground/15">
          {ROLE_GROUPS.map((role, index) => {
            const Icon = ICONS[role.key];
            return (
              <Reveal
                as="li"
                key={role.key}
                index={index}
                className="group grid gap-4 border-b border-foreground/15 py-8 transition-colors duration-base md:grid-cols-12 md:items-baseline md:gap-8 md:hover:bg-card/60"
              >
                <p className="text-eyebrow font-semibold text-warn-ink md:col-span-1">
                  {String(index + 1).padStart(2, "0")}
                </p>
                <h3 className="flex items-center gap-4 font-serif text-title font-medium md:col-span-4">
                  <Icon
                    aria-hidden="true"
                    size={22}
                    className="shrink-0 text-muted-foreground transition-colors duration-base group-hover:text-warn-ink"
                  />
                  {role.title}
                </h3>
                <ul className="grid gap-2 text-sm leading-relaxed text-muted-foreground sm:grid-cols-3 sm:gap-6 md:col-span-7">
                  {role.items.map((item) => (
                    <li key={item}>{item}</li>
                  ))}
                </ul>
              </Reveal>
            );
          })}
        </ul>
      </div>
    </section>
  );
}
