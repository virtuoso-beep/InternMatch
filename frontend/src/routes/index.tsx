import { createFileRoute, Link } from "@tanstack/react-router";
import { ROLES, ROLE_ORDER } from "@/lib/internmatch";
import heroInterns from "@/assets/hero-interns.jpg";
import sealUrl from "@/assets/umtc-seal.png";

export const Route = createFileRoute("/")({
  head: () => ({
    meta: [
      { title: "InternMatch — Internship Placement & Monitoring | UMTC" },
      {
        name: "description",
        content:
          "InternMatch centralizes student competencies, host establishments, deployments, and evaluations with semantic matching and geospatial accessibility analysis for UM Tagum College.",
      },
      { property: "og:title", content: "InternMatch — Internship Placement & Monitoring" },
      {
        property: "og:description",
        content:
          "Competency-based semantic matching, supervised reranking, cohort allocation, and internship monitoring for UM Tagum College.",
      },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary_large_image" },
    ],
  }),
  component: Landing,
});

const CAPABILITIES = [
  {
    title: "Semantic competency matching",
    body: "Multilingual sentence embeddings represent student competencies and host tasks, retrieved by cosine similarity.",
  },
  {
    title: "Supervised reranking",
    body: "A Logistic Regression reranker scores retrieved candidates using coordinator-annotated placement judgments.",
  },
  {
    title: "Constrained cohort allocation",
    body: "Allocation respects host capacity, MOA validity, program requirements, suitability, and travel burden.",
  },
  {
    title: "Geospatial accessibility",
    body: "Estimated student-to-establishment travel distance and placement distribution across localities.",
  },
  {
    title: "Monitoring & risk flags",
    body: "Certified hours, requirements, supervisor evaluations, hour-accrual pace, and completion-risk thresholds.",
  },
  {
    title: "Coordinator authority",
    body: "Recommendations are decision support. Coordinators review, modify, approve, or reject every placement.",
  },
];

const ROLE_SUMMARY: Record<string, string> = {
  student: "Profile, competencies, recommendations, hours, requirements.",
  coordinator: "Recommendations, allocation, assignment approval.",
  supervisor: "Establishment records, interns, hours, evaluations.",
  dean: "Program-level and institutional reports.",
  admin: "Accounts, permissions, audit trail, backups.",
};

const PROCESS = [
  { n: "01", title: "Preprocess", body: "Competency and task descriptions are cleaned for embedding." },
  { n: "02", title: "Retrieve", body: "Cosine similarity over multilingual sentence embeddings." },
  { n: "03", title: "Rerank", body: "Logistic Regression scores candidates on placement factors." },
  { n: "04", title: "Decide", body: "Coordinator reviews, modifies, approves, or rejects." },
];

function Landing() {
  return (
    <div className="min-h-screen bg-background">
      <header className="sticky top-0 z-30 border-b-4 border-warn bg-card/95 backdrop-blur">
        <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-5">
          <div className="flex items-center gap-2.5">
            <img src={sealUrl} alt="UM Tagum College seal" className="size-10" />
            <div>
              <p className="text-lg font-bold tracking-tight text-foreground">
                Intern<span className="text-brand">Match</span>
              </p>
              <p className="text-[10px] font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                UM Tagum College
              </p>
            </div>
          </div>
          <nav className="hidden items-center gap-7 text-sm text-foreground md:flex">
            <a href="#capabilities" className="hover:text-brand">Capabilities</a>
            <a href="#roles" className="hover:text-brand">Roles</a>
            <a href="#process" className="hover:text-brand">Process</a>
          </nav>
          <Link
            to="/auth"
            className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-brand-foreground transition-opacity hover:opacity-90"
          >
            Sign in
          </Link>
        </div>
      </header>

      <section className="bg-brand text-brand-foreground">
        <div className="mx-auto grid max-w-6xl items-center gap-10 px-5 py-16 lg:grid-cols-[1.05fr_1fr] lg:py-24">
          <div>
            <span className="inline-block rounded-full border border-warn/60 px-3 py-1 text-[11px] font-semibold tracking-[0.16em] text-warn uppercase">
              Capstone System · Department of Computing Education
            </span>
            <h1 className="mt-5 text-4xl leading-[1.05] font-bold tracking-tight lg:text-6xl">
              Internship placement decided by competency, not familiarity.
            </h1>
            <p className="mt-5 max-w-xl text-base opacity-90">
              InternMatch centralizes student profiles, host establishments, deployments, requirements, and evaluations
              for UM Tagum College — then ranks suitable placements using semantic matching, supervised learning, and
              geospatial accessibility analysis.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Link
                to="/auth"
                className="rounded-md bg-brand-foreground px-5 py-2.5 text-sm font-semibold text-brand transition-opacity hover:opacity-90"
              >
                Access the system →
              </Link>
              <a
                href="#capabilities"
                className="rounded-md border border-warn/70 px-5 py-2.5 text-sm font-semibold text-warn transition-colors hover:bg-brand-foreground/10"
              >
                See what it does
              </a>
            </div>
            <dl className="mt-10 grid max-w-lg grid-cols-3 gap-6 border-t border-brand-foreground/20 pt-6">
              {[
                ["2-stage", "Retrieval + reranking"],
                ["5", "User roles"],
                ["19", "Functional requirements"],
              ].map(([v, l]) => (
                <div key={l}>
                  <dt className="text-2xl font-bold text-warn">{v}</dt>
                  <dd className="text-[11px] tracking-wide uppercase opacity-80">{l}</dd>
                </div>
              ))}
            </dl>
          </div>
          <img
            src={heroInterns}
            alt="Practicum students collaborating with their supervisor around laptops"
            width={1280}
            height={960}
            className="w-full rounded-xl object-cover shadow-2xl"
          />
        </div>
      </section>

      <section id="capabilities" className="mx-auto max-w-6xl px-5 py-16 lg:py-20">
        <h2 className="max-w-2xl text-3xl font-bold tracking-tight text-foreground lg:text-4xl">
          One platform for placement, allocation, and monitoring
        </h2>
        <p className="mt-3 max-w-2xl text-sm text-muted-foreground">
          Every module maps to the functional requirements of the InternMatch capstone specification.
        </p>
        <div className="mt-10 grid overflow-hidden rounded-xl border border-border bg-card sm:grid-cols-2 lg:grid-cols-3">
          {CAPABILITIES.map((c) => (
            <article key={c.title} className="border-b border-border p-6 last:border-b-0 sm:border-r sm:[&:nth-child(2n)]:border-r-0 lg:[&:nth-child(2n)]:border-r lg:[&:nth-child(3n)]:border-r-0">
              <div className="mb-4 flex size-9 items-center justify-center rounded-md bg-warn-soft text-warn">◆</div>
              <h3 className="text-base font-semibold text-foreground">{c.title}</h3>
              <p className="mt-2 text-sm text-muted-foreground">{c.body}</p>
            </article>
          ))}
        </div>
      </section>

      <section id="roles" className="border-y border-border bg-card">
        <div className="mx-auto max-w-6xl px-5 py-16 lg:py-20">
          <h2 className="text-3xl font-bold tracking-tight text-foreground lg:text-4xl">Role-based access</h2>
          <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {ROLE_ORDER.map((r) => (
              <Link
                key={r}
                to={ROLES[r].base}
                className="rounded-lg border border-border bg-background p-5 transition-colors hover:border-brand"
              >
                <h3 className="text-sm font-semibold text-brand">{ROLES[r].title}</h3>
                <p className="mt-1 text-sm text-muted-foreground">{ROLE_SUMMARY[r]}</p>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section id="process" className="mx-auto max-w-6xl px-5 py-16 lg:py-20">
        <h2 className="text-3xl font-bold tracking-tight text-foreground lg:text-4xl">How a placement is formed</h2>
        <ol className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {PROCESS.map((s) => (
            <li key={s.n} className="border-t-2 border-warn pt-4">
              <p className="text-sm font-bold text-warn">{s.n}</p>
              <h3 className="mt-1 text-base font-semibold text-foreground">{s.title}</h3>
              <p className="mt-1 text-sm text-muted-foreground">{s.body}</p>
            </li>
          ))}
        </ol>
      </section>

      <footer className="bg-brand text-brand-foreground">
        <div className="mx-auto max-w-6xl px-5 py-10">
          <div className="flex items-center gap-2.5">
            <img src={sealUrl} alt="UM Tagum College seal" className="size-10" />
            <div>
              <p className="text-lg font-bold">
                Intern<span className="text-warn">Match</span>
              </p>
              <p className="text-[10px] font-semibold tracking-[0.18em] uppercase opacity-80">UM Tagum College</p>
            </div>
          </div>
          <p className="mt-4 max-w-3xl text-sm opacity-80">
            InternMatch · University of Mindanao Tagum College · Department of Computing Education. Recommendations are
            decision-support information; final placement authority remains with the practicum coordinator.
          </p>
        </div>
      </footer>
    </div>
  );
}
