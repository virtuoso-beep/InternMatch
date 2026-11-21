import { Reveal } from "./motion";
import { SectionHeading } from "./SectionHeading";

const PARTS = [
  {
    term: "Semantic similarity",
    body: "Competency profiles and opportunity tasks are turned into text embeddings with a multilingual model (multilingual-e5-base). Their cosine similarity is reported as a raw score to two decimals, never as a percentage.",
  },
  {
    term: "Haversine distance",
    body: "The straight-line distance between the student's location and the host establishment, in kilometres. It is a guide to travel burden, not road distance or travel time.",
  },
  {
    term: "Eligibility first",
    body: "Only opportunities the student can take are ranked: an active agreement, an open slot for the program and term, and the requirements in place.",
  },
  {
    term: "Coordinator approval",
    body: "Suggestions are decision support. The practicum coordinator approves, changes or rejects every placement, and each decision is recorded with its reason.",
  },
] as const;

/** The bold maroon block: an honest, short technical description of how suggestions are formed. */
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
            lead="InternMatch ranks opportunities by meaning, not keywords, then leaves the decision with a person."
          />
          <Reveal index={1} className="mt-12">
            <figure
              aria-label="Ranking formula"
              className="rounded-lg border border-header-foreground/15 p-6 font-mono text-sm leading-loose"
            >
              <p>
                similarity = cos(<span className="text-warn-light">e</span>
                <sub>student</sub>, <span className="text-warn-light">e</span>
                <sub>task</sub>)
              </p>
              <p>distance = haversine(student, host) km</p>
              <p className="text-header-foreground/70">rank: eligible only, then by similarity</p>
            </figure>
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
