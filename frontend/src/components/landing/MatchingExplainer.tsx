import { Reveal } from "./motion";
import { SectionHeading } from "./SectionHeading";

const PARTS = [
  {
    term: "Skills match",
    body: "We compare the skills in each student's profile with the tasks of each internship and give every pair a similarity score. A higher score means a closer fit. It is a way to compare options, not a percentage.",
  },
  {
    term: "Distance",
    body: "We measure the straight-line distance between the student and the host, in kilometres. It gives a sense of how far the student would go, not the actual travel route or time.",
  },
  {
    term: "Only real options",
    body: "A student only sees internships they can actually take: the host has a current agreement with the college that covers their program, and there is still a slot for their program this term.",
  },
  {
    term: "A person decides",
    body: "Suggestions help the practicum coordinator, who approves, changes or rejects every placement and records the reason.",
  },
] as const;

const STEPS = [
  "Keep only the internships the student can take.",
  "Compare the student's skills with each internship's tasks.",
  "Order them by how well they fit, then by distance.",
];

/** The bold maroon block: a plain, honest description of how suggestions are formed. */
export function MatchingExplainer() {
  return (
    <section aria-labelledby="matching-title" className="bg-header text-header-foreground">
      <div className="mx-auto grid max-w-7xl gap-16 px-4 py-24 sm:px-8 lg:grid-cols-12 lg:py-32">
        <div className="lg:col-span-5">
          <SectionHeading
            id="matching-title"
            number="04"
            label="How matching works"
            tone="inverse"
            title="Suggestions you can explain."
            lead="InternMatch suggests internships by comparing skills with tasks, then leaves the decision with a person."
          />
          <Reveal index={1} className="mt-12">
            <ol
              aria-label="How suggestions are formed"
              className="space-y-3 rounded-lg border border-header-foreground/15 p-6 text-sm leading-relaxed"
            >
              {STEPS.map((step, index) => (
                <li key={step} className="flex gap-3">
                  <span className="font-semibold text-warn-light">{index + 1}</span>
                  <span>{step}</span>
                </li>
              ))}
            </ol>
            <p className="mt-4 text-sm leading-relaxed text-header-foreground/75">
              Once coordinators have recorded enough judgments, the order can also learn from them.
              Until then, it uses skills match and distance only.
            </p>
          </Reveal>
        </div>
        <dl className="grid gap-x-12 gap-y-12 sm:grid-cols-2 lg:col-span-6 lg:col-start-7">
          {PARTS.map((part, index) => (
            <Reveal key={part.term} index={index} className="border-t border-warn-light/40 pt-6">
              <dt className="font-serif text-title font-medium">{part.term}</dt>
              <dd className="mt-4 leading-relaxed text-header-foreground/85">{part.body}</dd>
            </Reveal>
          ))}
        </dl>
      </div>
    </section>
  );
}
