import {
  CoordinatorStudents,
  CoordinatorRecommendations,
} from "@/components/im/CoordinatorRecords";
import { PageHeader } from "@/components/im/ui";
import { ChatWorkspace } from "@/components/im/ChatWorkspace";
import { NotificationList } from "@/components/im/NotificationList";
import { AllocationWorkspace } from "@/components/im/AllocationWorkspace";
import { ReportWorkspace } from "@/components/im/ReportWorkspace";
import { MoaWorkspace } from "@/components/im/MoaWorkspace";
import { LiveDashboard } from "@/components/im/LiveDashboard";
import { CoordinatorMonitoring } from "@/components/im/CoordinatorMonitoring";
import { ProgramMonitoringSettings } from "@/components/im/ProgramMonitoringSettings";
import { OpportunityWorkspace } from "@/components/im/OpportunityWorkspace";
import { RequirementWorkspace } from "@/components/im/RequirementWorkspace";
import { HostWorkspace } from "@/components/im/HostWorkspace";
import { AccessibilityMap } from "@/components/im/AccessibilityMap";
import { ProfileEditor } from "@/components/im/ProfileEditor";

export function CoordinatorSection({ section }: { section: string }) {
  switch (section) {
    case "inbox":
      return <ChatWorkspace />;
    case "notifications":
      return <NotificationList />;
    case "moa":
      return <MoaWorkspace />;
    case "profile":
      return <ProfileEditor role="coordinator" />;
    case "":
      return <LiveDashboard />;
    case "recommendations":
      return <CoordinatorRecommendations />;
    case "approvals":
      return <AllocationWorkspace />;
    case "students":
      return <CoordinatorStudents />;
    case "opportunities":
      return <OpportunityWorkspace />;
    case "hosts":
      return <HostWorkspace />;
    case "progress":
      return <CoordinatorMonitoring key="progress" mode="progress" />;
    case "requirements":
      return <RequirementWorkspace />;
    case "evaluations":
      return <CoordinatorMonitoring key="evaluations" mode="evaluations" />;
    case "monitoring-settings":
      return <ProgramMonitoringSettings />;
    case "analytics":
      return <ReportWorkspace title="Analytics and reports" />;
    case "map":
      return (
        <>
          <PageHeader
            title="Accessibility map"
            subtitle="Placement distribution across host establishments in the Davao Region."
          />
          <AccessibilityMap note="Use the map to balance placements against student travel burden." />
        </>
      );
    default:
      return <PageHeader title="Not found" subtitle="This coordinator page does not exist." />;
  }
}
