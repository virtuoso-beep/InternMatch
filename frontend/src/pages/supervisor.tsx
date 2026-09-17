import { useState } from "react";
import { toast } from "sonner";
import { useNavigate } from "@tanstack/react-router";
import {
  ActionDialog,
  downloadText,
  Bars,
  Button,
  Card,
  CardTitle,
  Field,
  Meter,
  PageHeader,
  Pill,
  Row,
  StatCard,
  StatGrid,
  Table,
  statusTone,
} from "@/components/im/ui";
import { ATTENDANCE, EVALUATIONS, INTERNS, OPPORTUNITIES } from "@/lib/internmatch";
import { ProfileEditor } from "@/components/im/ProfileEditor";

export function SupervisorSection({ section }: { section: string }) {
  switch (section) {
    case "profile":
      return <ProfileEditor role="supervisor" />;
    case "":
      return <Dashboard />;
    case "interns":
      return <Interns />;
    case "attendance":
      return <Attendance />;
    case "evaluations":
      return <Evaluations />;
    case "company":
      return <Company />;
    case "opportunities":
      return <Opportunities />;
    case "moa":
      return <Moa />;
    default:
      return <PageHeader title="Not found" subtitle="This supervisor page does not exist." />;
  }
}

function Dashboard() {
  return (
    <>
      <PageHeader title="Supervisor dashboard" subtitle="DataCore Solutions Inc. · Application Development" />
      <StatGrid>
        <StatCard label="Assigned interns" value="4" />
        <StatCard label="Hours to verify" value="6" tone="warn" />
        <StatCard label="Evaluations due" value="1" tone="brand" />
        <StatCard label="Average rating" value="4.4" tone="success" />
      </StatGrid>
      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle>Intern progress</CardTitle>
          <Table head={["Intern", "Program", "Hours", "Completion", "Status"]}>
            {INTERNS.map((i) => (
              <Row key={i.name}>
                <td className="font-medium">{i.name}</td>
                <td className="text-muted-foreground">{i.program}</td>
                <td>
                  {i.hours} / {i.required}
                </td>
                <td className="w-40">
                  <Meter value={(i.hours / i.required) * 100} tone={i.status === "At risk" ? "warn" : "success"} />
                </td>
                <td>
                  <Pill tone={statusTone(i.status)}>{i.status}</Pill>
                </td>
              </Row>
            ))}
          </Table>
        </Card>
        <Card>
          <CardTitle>Pending actions</CardTitle>
          <div className="space-y-3 text-sm">
            {[
              ["Verify 6 attendance entries", "warn"],
              ["Submit midterm evaluation for J. Ramos", "brand"],
              ["Renew MOA (expires Jun 2027)", "muted"],
              ["Review 2 open internship slots", "info"],
            ].map(([label, tone]) => (
              <div key={label} className="flex items-center justify-between gap-3 rounded-md border border-border p-3">
                <span>{label}</span>
                <Pill tone={tone as never}>Action</Pill>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </>
  );
}

function Interns() {
  const navigate = useNavigate();
  const [journal, setJournal] = useState<string | null>(null);
  return (
    <>
      <PageHeader title="Assigned interns" subtitle="Students currently deployed to your establishment." />
      <div className="grid gap-4 xl:grid-cols-2">
        {INTERNS.map((i) => (
          <Card key={i.name}>
            <div className="flex items-start justify-between gap-3">
              <div>
                <h3 className="text-lg font-semibold">{i.name}</h3>
                <p className="text-sm text-muted-foreground">{i.program} · Web Development Intern</p>
              </div>
              <Pill tone={statusTone(i.status)}>{i.status}</Pill>
            </div>
            <div className="mt-4 grid grid-cols-3 gap-3">
              <Field label="Hours" value={`${i.hours} / ${i.required}`} />
              <Field label="Rating" value={`${i.rating} / 5.0`} />
              <Field label="Period" value="Jul – Nov 2026" />
            </div>
            <div className="mt-4">
              <Meter value={(i.hours / i.required) * 100} tone={i.status === "At risk" ? "warn" : "success"} />
            </div>
            <div className="mt-4 flex gap-2">
              <Button onClick={() => navigate({ to: "/supervisor/$section", params: { section: "evaluations" } })}>Log evaluation</Button>
              <Button variant="outline" onClick={() => setJournal(i.name)}>View journal</Button>
            </div>
          </Card>
        ))}
      </div>
      {journal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 px-4" role="presentation" onClick={() => setJournal(null)}>
          <div role="dialog" aria-modal="true" className="w-full max-w-lg rounded-lg border border-border bg-card p-5 shadow-xl" onClick={(event) => event.stopPropagation()}>
            <CardTitle right={<Button variant="ghost" onClick={() => setJournal(null)}>Close</Button>}>Journal · {journal}</CardTitle>
            <p className="text-sm text-muted-foreground">Week 6 journal: completed assigned development tasks, reviewed pull requests, and documented testing results.</p>
          </div>
        </div>
      )}
    </>
  );
}

function Attendance() {
  const [verified, setVerified] = useState(false);
  return (
    <>
      <PageHeader
        title="Attendance & hours"
        subtitle="Verify daily time records before they count toward required hours."
        action={<Button onClick={() => setVerified(true)}>{verified ? "All verified" : "Verify all pending"}</Button>}
      />
      <Card>
        <Table head={["Date", "Intern", "Time in", "Time out", "Hours", "Status"]}>
          {ATTENDANCE.map((a, i) => (
            <Row key={i}>
              <td className="text-muted-foreground">{a.date}</td>
              <td className="font-medium">{a.intern}</td>
              <td>{a.timeIn}</td>
              <td>{a.timeOut}</td>
              <td>{a.hours}</td>
              <td>
                <Pill tone={verified || a.status !== "Pending" ? "success" : statusTone(a.status)}>{verified || a.status !== "Pending" ? "Verified" : a.status}</Pill>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Evaluations() {
  const [scores, setScores] = useState<Record<string, number>>({
    "Technical skill": 4,
    "Work ethic": 4,
    Communication: 4,
    Initiative: 4,
  });
  const [submitted, setSubmitted] = useState(false);
  const [draftSaved, setDraftSaved] = useState(false);

  return (
    <>
      <PageHeader title="Evaluations" subtitle="Rate interns on technical skill, work ethic, and communication." />
      <Card className="mb-5">
        <CardTitle>Midterm evaluation · J. Ramos</CardTitle>
        <div className="space-y-4">
          {["Technical skill", "Work ethic", "Communication", "Initiative"].map((c) => (
            <div key={c} className="flex items-center justify-between gap-4">
              <span className="text-sm">{c}</span>
              <div className="flex gap-1">
                {[1, 2, 3, 4, 5].map((n) => (
                  <button
                    type="button"
                    aria-label={`${c}: ${n} out of 5`}
                    onClick={() => setScores((current) => ({ ...current, [c]: n }))}
                    key={n}
                    className={`flex size-8 items-center justify-center rounded-md border text-sm ${
                      n <= (scores[c] ?? 0)
                        ? "border-brand bg-brand-soft font-semibold text-brand"
                        : "border-border text-muted-foreground"
                    }`}
                  >
                    {n}
                  </button>
                ))}
              </div>
            </div>
          ))}
        </div>
        <div className="mt-5 flex gap-2">
          <Button onClick={() => { setSubmitted(true); setDraftSaved(false); }}>Submit evaluation</Button>
          <Button variant="outline" onClick={() => { setDraftSaved(true); setSubmitted(false); }}>Save draft</Button>
        </div>
        {submitted && <p className="mt-3 text-sm font-medium text-success">Evaluation submitted for review.</p>}
        {draftSaved && <p className="mt-3 text-sm font-medium text-brand">Draft saved. You can continue later.</p>}
      </Card>
      <Card>
        <CardTitle>Submitted evaluations</CardTitle>
        <Table head={["Intern", "Period", "Overall", "Status"]}>
          {EVALUATIONS.map((e) => (
            <Row key={e.intern + e.period}>
              <td className="font-medium">{e.intern}</td>
              <td className="text-muted-foreground">{e.period}</td>
              <td className="font-semibold">{e.overall}</td>
              <td>
                <Pill tone={statusTone(e.status)}>{e.status}</Pill>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Company() {
  const navigate = useNavigate();
  return (
    <>
      <PageHeader title="Company profile" subtitle="Details shown to students in recommendations." action={<Button onClick={() => navigate({ to: "/supervisor/$section", params: { section: "profile" } })}>Edit profile</Button>} />
      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle>Establishment details</CardTitle>
          <div className="grid gap-5 sm:grid-cols-2">
            <Field label="Name" value="DataCore Solutions Inc." />
            <Field label="Industry" value="Software Development" />
            <Field label="Address" value="Pioneer Ave., Tagum City, Davao del Norte" />
            <Field label="Distance from campus" value="3.2 km" />
            <Field label="Contact person" value="Rico Fernandez" />
            <Field label="Email" value="rfernandez@datacore.ph" />
          </div>
        </Card>
        <Card>
          <CardTitle>Capacity</CardTitle>
          <div className="space-y-4">
            <Field label="Total slots" value="4" />
            <Field label="Open slots" value="2" />
            <Field label="Accessibility rating" value={<Pill tone="success">High</Pill>} />
            <Field label="Student rating" value="4.6 / 5.0" />
          </div>
        </Card>
      </div>
    </>
  );
}

function Opportunities() {
  const [dialogOpen, setDialogOpen] = useState(false);
  return (
    <>
      <PageHeader title="Internship opportunities" subtitle="Postings matched against student competency profiles." action={<Button onClick={() => setDialogOpen(true)}>Post opportunity</Button>} />
      <div className="grid gap-4 xl:grid-cols-2">
        {OPPORTUNITIES.map((o) => (
          <Card key={o.title}>
            <div className="flex items-start justify-between gap-3">
              <div>
                <h3 className="text-lg font-semibold">{o.title}</h3>
                <p className="text-sm text-muted-foreground">{o.slots}</p>
              </div>
              <Pill tone={statusTone(o.status)}>{o.status}</Pill>
            </div>
            <div className="mt-4 flex flex-wrap gap-2">
              {o.competencies.map((c) => (
                <Pill key={c}>{c}</Pill>
              ))}
            </div>
          </Card>
        ))}
      </div>
      <ActionDialog
        open={dialogOpen}
        onOpenChange={setDialogOpen}
        title="Post internship opportunity"
        fields={[{ name: "title", label: "Opportunity title", placeholder: "e.g. Frontend Developer Intern" }, { name: "slots", label: "Available slots", type: "number", placeholder: "2" }]}
        onSubmit={(values) => { setDialogOpen(false); toast.success(`${values['title']} posted with ${values['slots']} slot(s).`); }}
      />
    </>
  );
}

function Moa() {
  const [renewalRequested, setRenewalRequested] = useState(false);
  return (
    <>
      <PageHeader title="Memorandum of agreement" subtitle="Agreement status between your establishment and the university." />
      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle right={<Pill tone="success">Active</Pill>}>Current agreement</CardTitle>
          <div className="grid gap-5 sm:grid-cols-2">
            <Field label="Reference number" value="MOA-2025-0142" />
            <Field label="Effective date" value="Jun 15, 2025" />
            <Field label="Expiry date" value="Jun 14, 2027" />
            <Field label="Signatories" value="Dr. R. Villanueva · R. Fernandez" />
            <Field label="Covered programs" value="BSIT, BSCS, BSIS" />
            <Field label="Max interns per term" value="4" />
          </div>
          <div className="mt-5 flex gap-2">
            <Button variant="outline" onClick={() => downloadText("MOA-2025-0142.txt", "Memorandum of Agreement\nReference: MOA-2025-0142\nStatus: Active\n")}>Download PDF</Button>
            <Button onClick={() => setRenewalRequested(true)}>{renewalRequested ? "Renewal requested" : "Request renewal"}</Button>
          </div>
          {renewalRequested && <p className="mt-3 text-sm font-medium text-success">Renewal request submitted to the practicum office.</p>}
        </Card>
        <Card>
          <CardTitle>Compliance</CardTitle>
          <Bars
            data={[
              { label: "Documents on file", value: 100 },
              { label: "Evaluations submitted", value: 75 },
              { label: "Attendance verified", value: 88 },
            ]}
          />
        </Card>
      </div>
    </>
  );
}
