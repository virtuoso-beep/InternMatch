import { NotificationList } from "@/components/im/NotificationList";
import { MoaWorkspace } from "@/components/im/MoaWorkspace";
import { LiveDashboard } from "@/components/im/LiveDashboard";
import { ProgramMonitoringSettings } from "@/components/im/ProgramMonitoringSettings";
import {
  Bars,
  ActionDialog,
  Button,
  downloadText,
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
import { ProgramRegistry } from "@/components/im/ProgramRegistry";
import { AuditLog } from "@/components/im/AuditLog";
import { OpportunityWorkspace } from "@/components/im/OpportunityWorkspace";
import { HostWorkspace } from "@/components/im/HostWorkspace";
import { AccountRegistry } from "@/components/im/AccountRegistry";
import { ProfileEditor } from "@/components/im/ProfileEditor";

export function AdminSection({ section }: { section: string }) {
  switch (section) {
    case "notifications": return <NotificationList />;
    case "monitoring-settings": return <ProgramMonitoringSettings />;
    case "profile":
      return <ProfileEditor role="admin" />;
    case "":
      return <LiveDashboard />;
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
  const perms = ["View students", "Approve placements", "Submit evaluations", "Manage users", "Export reports"];
  const roles = ["Student", "Coordinator", "Supervisor", "Dean", "Admin"];
  const matrix: Record<string, boolean[]> = {
    Student: [false, false, false, false, false],
    Coordinator: [true, true, false, false, true],
    Supervisor: [true, false, true, false, false],
    Dean: [true, false, false, false, true],
    Admin: [true, false, false, true, false],
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


function Programs() {
  return <ProgramRegistry />;
}

function Backup() {
  return <>
    <PageHeader title="Backup & recovery" subtitle="Database backups are performed by the system operator." />
    <Card>
      <CardTitle>Operator backup procedure</CardTitle>
      <p className="text-sm">Use the documented Docker database backup procedure and retain private uploaded documents alongside the SQL backup. Verify recovery in a separate database before accepting a restore point.</p>
      <p className="mt-3 text-sm">This page does not execute backups or display unverified restore points. The local development backup and restore evidence is recorded in the implementation audit.</p>
      <p className="mt-3 text-sm">Production backup scheduling, storage location, retention and recovery targets require the deployment configuration. They have not been configured.</p>
    </Card>
  </>;
}
