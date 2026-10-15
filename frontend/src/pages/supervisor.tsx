import { PageHeader } from "@/components/im/ui";
import { ChatWorkspace } from "@/components/im/ChatWorkspace";
import { NotificationList } from "@/components/im/NotificationList";
import { MoaWorkspace } from "@/components/im/MoaWorkspace";
import { LiveDashboard } from "@/components/im/LiveDashboard";
import { MonitoringWorkspace } from "@/components/im/MonitoringWorkspace";
import { OpportunityWorkspace } from "@/components/im/OpportunityWorkspace";
import { HostWorkspace } from "@/components/im/HostWorkspace";
import { ProfileEditor } from "@/components/im/ProfileEditor";

export function SupervisorSection({ section }: { section: string }) {
  switch (section) {
    case "inbox":
      return <ChatWorkspace />;
    case "notifications":
      return <NotificationList />;
    case "profile":
      return <ProfileEditor role="supervisor" />;
    case "":
      return <LiveDashboard />;
    case "interns":
      return <MonitoringWorkspace key="interns" view="interns" />;
    case "attendance":
      return <MonitoringWorkspace key="attendance" view="attendance" />;
    case "evaluations":
      return <MonitoringWorkspace key="evaluations" view="evaluations" />;
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
