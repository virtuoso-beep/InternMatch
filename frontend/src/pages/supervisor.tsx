import { PageHeader } from "@/components/im/ui";
import { NotificationList } from "@/components/im/NotificationList";
import { MoaWorkspace } from "@/components/im/MoaWorkspace";
import { LiveDashboard } from "@/components/im/LiveDashboard";
import { MonitoringWorkspace } from "@/components/im/MonitoringWorkspace";
import { OpportunityWorkspace } from "@/components/im/OpportunityWorkspace";
import { HostWorkspace } from "@/components/im/HostWorkspace";
import { ProfileEditor } from "@/components/im/ProfileEditor";

export function SupervisorSection({ section }: { section: string }) {
  switch (section) {
    case "notifications": return <NotificationList />;
    case "profile":
      return <ProfileEditor role="supervisor" />;
    case "":
      return <LiveDashboard />;
    case "interns":
      return <MonitoringWorkspace />;
    case "attendance":
      return <MonitoringWorkspace />;
    case "evaluations":
      return <MonitoringWorkspace />;
    case "company":
      return <HostWorkspace />;
    case "opportunities":
      return <OpportunityWorkspace />;
    case "moa":
      return <MoaWorkspace />;
    default:
      return <PageHeader title="Not found" subtitle="This supervisor page does not exist." />;
  }
}
