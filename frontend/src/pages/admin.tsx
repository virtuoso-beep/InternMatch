import { useState } from "react";
import { AdminTerms, AdminSettings } from "@/components/im/AdminWorkspace";
import { AdminOverview } from "@/components/im/AdminOverview";
import { ExcelImportWorkspace } from "@/components/im/ExcelImportWorkspace";
import { ImportHistory } from "@/components/im/ImportHistory";
import { BackupHistory } from "@/components/im/BackupHistory";
import { NotificationList } from "@/components/im/NotificationList";
import { ChatSettings } from "@/components/im/ChatWorkspace";
import { MoaWorkspace } from "@/components/im/MoaWorkspace";
import { ProgramMonitoringSettings } from "@/components/im/ProgramMonitoringSettings";
import {
  Button,
  downloadText,
  Card,
  CardTitle,
  PageHeader,
  Pill,
  Row,
  Table,
} from "@/components/im/ui";
import { ProgramRegistry } from "@/components/im/ProgramRegistry";
import { AuditLog } from "@/components/im/AuditLog";
import { OpportunityWorkspace } from "@/components/im/OpportunityWorkspace";
import { HostWorkspace } from "@/components/im/HostWorkspace";
import { AccountRegistry } from "@/components/im/AccountRegistry";
import { ProfileEditor } from "@/components/im/ProfileEditor";

export function AdminSection({ section }: { section: string }) {
  switch (section) {
    case "chat-settings":
      return <ChatSettings />;
    case "notifications":
      return <NotificationList />;
    case "monitoring-settings":
      return <ProgramMonitoringSettings />;
    case "profile":
      return <ProfileEditor role="admin" />;
    case "":
      return <AdminOverview />;
    case "students":
      return <AccountRegistry studentsOnly />;
    case "student-import":
      return <StudentImportPage />;
    case "terms":
      return <AdminTerms />;
    case "settings":
      return <AdminSettings />;
    case "users":
      return <AccountRegistry />;
    case "roles":
      return <Roles />;
    case "opportunities":
      return <OpportunityWorkspace />;
    case "hosts":
      return <HostWorkspace />;
    case "programs":
      return <Programs />;
    case "moa":
      return <MoaWorkspace />;
    case "audit":
      return <AuditLog />;
    case "backup":
      return <Backup />;
    default:
      return <PageHeader title="Not found" subtitle="This admin page does not exist." />;
  }
}

function Roles() {
  const [search, setSearch] = useState("");
  const roles = ["Student", "Coordinator", "Supervisor", "Dean", "Admin"];
  const permissions: [string, string, string[]][] = [
    ["Manage own profile", "Only the signed-in account.", roles],
    ["Manage own competencies", "Student’s own competency records.", ["Student"]],
    ["Submit own requirements", "Student’s own enrollment records.", ["Student"]],
    ["View own placement", "Student’s own placement.", ["Student"]],
    [
      "View program records",
      "Assigned programs; Dean access is limited to permitted summaries and approved reports.",
      ["Coordinator", "Dean"],
    ],
    [
      "Decide placements",
      "Coordinator’s assigned programs; capacity, MOA and readiness checks apply.",
      ["Coordinator"],
    ],
    ["Review requirements", "Coordinator’s assigned programs.", ["Coordinator"]],
    [
      "View reports",
      "Assigned programs; Dean sees coordinator-approved snapshots only.",
      ["Coordinator", "Dean"],
    ],
    ["View audit trail", "Authorized system or program scope.", ["Coordinator", "Dean", "Admin"]],
    [
      "Manage hosts",
      "Coordinator program scope or administrator registry access.",
      ["Coordinator", "Admin"],
    ],
    ["Manage assigned host", "Only assigned host establishments.", ["Supervisor"]],
    ["Monitor assigned interns", "Only assigned placements.", ["Supervisor"]],
    ["Submit evaluations", "Assigned internships and configured rubrics.", ["Supervisor"]],
    ["Manage accounts", "Account administration does not grant placement approval.", ["Admin"]],
    ["Manage academic records", "Program and curriculum configuration.", ["Admin"]],
    ["Manage system", "System configuration; no access to conversation content.", ["Admin"]],
  ];
  const visible = permissions.filter(([name, scope]) =>
    `${name} ${scope}`.toLowerCase().includes(search.trim().toLowerCase()),
  );
  return (
    <>
      <PageHeader
        title="Roles & permissions"
        subtitle="Role capabilities and record-level restrictions. This is a read-only reference; account roles are managed in User Accounts."
      />
      <div className="mb-5 flex flex-wrap gap-3">
        <label className="text-sm">
          Find permission
          <input
            type="search"
            className="ml-2 rounded border p-2"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
          />
        </label>
        <Button variant="outline" onClick={() => setSearch("")}>
          Clear search
        </Button>
      </div>
      <Card>
        <p className="mb-4 text-sm text-muted-foreground">
          Administrators cannot approve placements or submit supervisor evaluations. Each allowed
          capability remains subject to server-side policies and assignments.
        </p>
        <Table head={["Permission / scope", ...roles]}>
          {visible.map(([name, scope, allowed]) => (
            <Row key={name}>
              <td>
                <p className="font-semibold">{name}</p>
                <p className="mt-1 max-w-sm text-xs text-muted-foreground">{scope}</p>
              </td>
              {roles.map((role) => (
                <td key={role}>
                  <Pill tone={allowed.includes(role) ? "success" : "muted"}>
                    {allowed.includes(role) ? "Allowed" : "Not granted"}
                  </Pill>
                </td>
              ))}
            </Row>
          ))}
        </Table>
        {!visible.length && <p>No permissions match your search.</p>}
      </Card>
    </>
  );
}
function Programs() {
  return <ProgramRegistry />;
}

function Backup() {
  const checklist = `InternMatch backup and recovery checklist

This checklist is guidance, not a backup or verification record.

1. Confirm the deployment, database, backup destination, retention and recovery targets with the system operator.
2. Back up the database and all uploaded documents, including private files. Use deploy/recovery.py backup; it includes both private and public document directories.
3. Retain the application version and securely preserve the encryption key and required configuration with restricted access.
4. Record the timestamp, artifact sizes, checksums and backup result. Confirm that both database and document archives are readable.
5. Restore into an isolated environment first. Check account sign-in, program assignments, placements, hours, evaluations, reports and private document downloads.
6. Record recovery duration and verification results before accepting the restore point.

Local backup history is connected to this screen. Scheduling runs through Windows Task Scheduler. Restoring the live database requires a separate operator procedure and will overwrite data.
`;
  return (
    <>
      <PageHeader
        title="Backup & recovery"
        subtitle="Database backups are performed by the system operator."
      />
      <div className="grid items-start gap-5 lg:grid-cols-[2fr_1fr]">
        <Card>
          <CardTitle>Restore points</CardTitle>
          <p className="my-4 rounded border border-dashed p-5 text-sm text-muted-foreground">
            Recorded backup results and isolated recovery verification appear below.
          </p>
          <p className="text-sm">
            A restore point should include the database, public and private uploaded documents, and
            recorded recovery verification.
          </p>
          <div className="my-4">
            <Button
              variant="outline"
              onClick={() => downloadText("internmatch-recovery-checklist.txt", checklist)}
            >
              Download recovery checklist
            </Button>
          </div>
          <details className="mt-4 border-t pt-4">
            <summary className="cursor-pointer font-semibold">Recovery verification steps</summary>
            <ol className="mt-3 list-decimal space-y-2 pl-5 text-sm">
              <li>Check that database and document archives belong to the same backup.</li>
              <li>
                Restore into a separate environment and verify account sign-in and program access.
              </li>
              <li>Check placements, hours, evaluations, reports and private document downloads.</li>
              <li>Record recovery duration and results before accepting the restore point.</li>
            </ol>
          </details>
        </Card>
        <Card>
          <CardTitle>Backup configuration</CardTitle>
          <dl className="mt-4 space-y-4 text-sm">
            <div>
              <dt className="text-xs text-muted-foreground">Storage usage</dt>
              <dd>Not connected</dd>
            </div>
            <div>
              <dt className="text-xs text-muted-foreground">Scheduling</dt>
              <dd>See local schedule in backup history below.</dd>
            </div>
            <div>
              <dt className="text-xs text-muted-foreground">Retention and recovery targets</dt>
              <dd>Local backups are retained until operator review; no automatic deletion.</dd>
            </div>
          </dl>
        </Card>
      </div>
      <div className="mt-5">
        <BackupHistory />
      </div>
      <div className="mt-5">
        <Card>
          <CardTitle>Operator backup procedure</CardTitle>
          <p className="text-sm">
            Use the documented Docker database backup procedure and retain private uploaded
            documents alongside the SQL backup. Verify recovery in a separate database before
            accepting a restore point.
          </p>
          <p className="mt-3 text-sm">
            This page displays recorded backup and verification status. The local development backup
            and restore evidence is recorded in the implementation audit.
          </p>
          <p className="mt-3 text-sm">
            Backups are stored locally outside the public web directory. A copy on this computer
            does not protect against loss of the computer.
          </p>
          <p className="mt-3 text-sm">
            The backup tool includes private and public uploads and SHA-256 checksums. Recovery
            drills use a separate database and document folder.
          </p>
        </Card>
      </div>
    </>
  );
}

function StudentImportPage() {
  const [tab, setTab] = useState("import");
  return (
    <>
      <PageHeader
        title="Student Import"
        subtitle="Import student lists and provision accounts after validation"
      />
      <Card>
        <div role="tablist" aria-label="Student import" className="mb-5 flex border-b">
          {[
            ["import", "Import Students"],
            ["history", "Import History"],
          ].map(([key, label]) => (
            <button
              role="tab"
              key={key}
              aria-selected={tab === key}
              className={`border-b-2 px-4 py-3 text-sm ${tab === key ? "border-brand font-bold text-brand" : "border-transparent"}`}
              onClick={() => setTab(key!)}
            >
              {label}
            </button>
          ))}
        </div>
        <div hidden={tab !== "import"}>
          <ExcelImportWorkspace module="student" />
        </div>
        {tab === "history" && <ImportHistory />}
      </Card>
    </>
  );
}
