import { NotificationList } from "@/components/im/NotificationList";
import { DeanDashboard } from "@/components/im/DeanDashboard";
import { ProfileEditor } from "@/components/im/ProfileEditor";
import { ReportWorkspace } from "@/components/im/ReportWorkspace";
import { ProgramAnalytics } from "@/components/im/ProgramAnalytics";
import { AuditLog } from "@/components/im/AuditLog";
import { PageHeader } from "@/components/im/ui";

export function DeanSection({ section }: { section: string }) {
  switch (section) {
    case "notifications":
      return <NotificationList />;
    case "profile":
      return <ProfileEditor role="dean" />;
    case "":
      return <DeanDashboard />;
    case "performance":
    case "hosts":
    case "equity":
    case "analytics":
      return <ProgramAnalytics section={section} />;
    case "accreditation":
      return (
        <ReportWorkspace
          key="accreditation"
          title="Accreditation reports"
          presentation="accreditation"
        />
      );
    case "export":
      return <ReportWorkspace key="export" title="Export data" presentation="export" />;
    case "audit":
      return <AuditLog />;
    default:
      return <PageHeader title="Not found" subtitle="This dean page does not exist." />;
  }
}
