import { useState } from "react";
import {
  Bars,
  Button,
  Card,
  CardTitle,
  Field,
  FilterChips,
  PageHeader,
  Pill,
  Row,
  StatCard,
  StatGrid,
  Table,
  statusTone,
} from "@/components/im/ui";
import { AUDIT, HOSTS, PROGRAMS, USERS } from "@/lib/internmatch";
import { ProfileEditor } from "@/components/im/ProfileEditor";

export function AdminSection({ section }: { section: string }) {
  switch (section) {
    case "profile":
      return <ProfileEditor role="admin" />;
    case "":
      return <Dashboard />;
    case "users":
      return <Users />;
    case "roles":
      return <Roles />;
    case "hosts":
      return <Hosts />;
    case "programs":
      return <Programs />;
    case "moa":
      return <MoaRecords />;
    case "audit":
      return <Audit />;
    case "backup":
      return <Backup />;
    default:
      return <PageHeader title="Not found" subtitle="This admin page does not exist." />;
  }
}

function Dashboard() {
  return (
    <>
      <PageHeader title="System dashboard" subtitle="InternMatch platform health and usage." />
      <StatGrid>
        <StatCard label="Active accounts" value="612" />
        <StatCard label="Sessions today" value="184" tone="success" />
        <StatCard label="Failed logins (24h)" value="7" tone="warn" />
        <StatCard label="Last backup" value="2h ago" tone="success" />
      </StatGrid>
      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle>Recent activity</CardTitle>
          <Table head={["Timestamp", "Actor", "Action"]}>
            {AUDIT.slice(0, 5).map((a) => (
              <Row key={a.time}>
                <td className="text-muted-foreground">{a.time}</td>
                <td className="font-medium">{a.actor}</td>
                <td>{a.action}</td>
              </Row>
            ))}
          </Table>
        </Card>
        <Card>
          <CardTitle>Service status</CardTitle>
          <div className="space-y-3 text-sm">
            {[
              ["Web application", "Healthy"],
              ["Database", "Healthy"],
              ["Recommendation service", "Healthy"],
              ["File storage", "Healthy"],
              ["Scheduled backups", "Healthy"],
            ].map(([svc, st]) => (
              <div key={svc} className="flex items-center justify-between gap-3">
                <span>{svc}</span>
                <Pill tone={statusTone(st!)}>{st}</Pill>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </>
  );
}

function Users() {
  const [filter, setFilter] = useState("All");
  const rows = USERS.filter((u) => filter === "All" || u.role.includes(filter));
  return (
    <>
      <PageHeader
        title="User accounts"
        subtitle="612 active accounts across all roles"
        action={<Button>+ Add user</Button>}
      />
      <FilterChips
        options={["All", "Student", "Coordinator", "Supervisor", "Dean"]}
        value={filter}
        onChange={setFilter}
      />
      <Card>
        <Table head={["Name", "Role", "Last login", "Status", "Action"]}>
          {rows.map((u) => (
            <Row key={u.name}>
              <td className="font-medium">{u.name}</td>
              <td className="text-muted-foreground">{u.role}</td>
              <td className="text-muted-foreground">{u.login}</td>
              <td>
                <Pill tone={statusTone(u.status)}>{u.status}</Pill>
              </td>
              <td>
                <button type="button" className="text-sm font-semibold text-brand hover:underline">
                  {u.status === "Pending" ? "Resend invite" : "Edit"}
                </button>
              </td>
            </Row>
          ))}
        </Table>
        <p className="mt-4 text-sm text-muted-foreground">Showing {rows.length} of 612 accounts</p>
      </Card>
    </>
  );
}

function Roles() {
  const perms = ["View students", "Approve placements", "Submit evaluations", "Manage users", "Export reports"];
  const roles = ["Student", "Coordinator", "Supervisor", "Dean", "Admin"];
  const matrix: Record<string, boolean[]> = {
    Student: [false, false, false, false, false],
    Coordinator: [true, true, false, false, true],
    Supervisor: [true, false, true, false, false],
    Dean: [true, false, false, false, true],
    Admin: [true, true, true, true, true],
  };
  return (
    <>
      <PageHeader title="Roles & permissions" subtitle="Role-based access control across the platform." />
      <Card>
        <Table head={["Permission", ...roles]}>
          {perms.map((p, i) => (
            <Row key={p}>
              <td className="font-medium">{p}</td>
              {roles.map((r) => (
                <td key={r}>
                  <Pill tone={matrix[r]![i] ? "success" : "muted"}>{matrix[r]![i] ? "Allowed" : "—"}</Pill>
                </td>
              ))}
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Hosts() {
  return (
    <>
      <PageHeader title="Host establishments" subtitle="Master record of partner organisations." action={<Button>+ Add establishment</Button>} />
      <Card>
        <Table head={["Establishment", "Industry", "City", "Slots", "MOA"]}>
          {HOSTS.map((h) => (
            <Row key={h.name}>
              <td className="font-medium">{h.name}</td>
              <td className="text-muted-foreground">{h.field}</td>
              <td className="text-muted-foreground">{h.city}</td>
              <td>
                {h.slotsOpen}/{h.slotsTotal}
              </td>
              <td>
                <Pill tone={statusTone(h.moa)}>{h.moa}</Pill>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Programs() {
  return (
    <>
      <PageHeader title="Programs & curriculum" subtitle="Programs and their mapped practicum competencies." />
      <div className="grid gap-4 lg:grid-cols-3">
        {PROGRAMS.map((p) => (
          <Card key={p.code}>
            <CardTitle>{p.code}</CardTitle>
            <p className="text-sm text-muted-foreground">{p.name}</p>
            <div className="mt-4 grid grid-cols-2 gap-3">
              <Field label="Students" value={String(p.students)} />
              <Field label="Partners" value={String(p.partners)} />
              <Field label="Required hours" value="486" />
              <Field label="Competencies" value="12 mapped" />
            </div>
          </Card>
        ))}
      </div>
    </>
  );
}

function MoaRecords() {
  return (
    <>
      <PageHeader title="MOA records" subtitle="Agreements between the university and host establishments." />
      <StatGrid>
        <StatCard label="Active MOAs" value="50" tone="success" />
        <StatCard label="Expiring in 90 days" value="6" tone="warn" />
        <StatCard label="Expired" value="2" tone="brand" />
        <StatCard label="Pending signature" value="3" tone="warn" />
      </StatGrid>
      <Card>
        <Table head={["Reference", "Establishment", "Effective", "Status"]}>
          {HOSTS.map((h, i) => (
            <Row key={h.name}>
              <td className="font-medium">MOA-2025-{140 + i}</td>
              <td>{h.name}</td>
              <td className="text-muted-foreground">Jun 15, 2025</td>
              <td>
                <Pill tone={statusTone(h.moa)}>{h.moa}</Pill>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Audit() {
  return (
    <>
      <PageHeader title="Audit trail" subtitle="Immutable log of all system activity." action={<Button variant="outline">Export log</Button>} />
      <Card>
        <Table head={["Timestamp", "Actor", "Action", "Target", "IP address"]}>
          {AUDIT.map((a) => (
            <Row key={a.time}>
              <td className="text-muted-foreground">{a.time}</td>
              <td className="font-medium">{a.actor}</td>
              <td>{a.action}</td>
              <td className="text-muted-foreground">{a.target}</td>
              <td className="text-muted-foreground">{a.ip}</td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Backup() {
  return (
    <>
      <PageHeader title="Backup & recovery" subtitle="Scheduled backups and restore points." action={<Button>Run backup now</Button>} />
      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle>Restore points</CardTitle>
          <Table head={["Created", "Type", "Size", "Status"]}>
            {[
              ["Aug 27, 2026 06:00", "Automated · Daily", "1.8 GB", "Completed"],
              ["Aug 26, 2026 06:00", "Automated · Daily", "1.8 GB", "Completed"],
              ["Aug 25, 2026 06:00", "Automated · Daily", "1.7 GB", "Completed"],
              ["Aug 24, 2026 21:14", "Manual", "1.7 GB", "Completed"],
            ].map((r) => (
              <Row key={r[0]}>
                <td className="font-medium">{r[0]}</td>
                <td className="text-muted-foreground">{r[1]}</td>
                <td>{r[2]}</td>
                <td>
                  <Pill tone="success">{r[3]}</Pill>
                </td>
              </Row>
            ))}
          </Table>
        </Card>
        <Card>
          <CardTitle>Storage usage</CardTitle>
          <Bars
            data={[
              { label: "Database", value: 42 },
              { label: "Documents", value: 61 },
              { label: "Backups", value: 35 },
            ]}
          />
          <div className="mt-5 space-y-4">
            <Field label="Backup schedule" value="Daily at 06:00 (Asia/Manila)" />
            <Field label="Retention" value="30 daily · 12 monthly" />
          </div>
        </Card>
      </div>
    </>
  );
}
