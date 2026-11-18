import { useEffect, useState } from "react";
import {
  CoordinatorStudents,
  CoordinatorRecommendations,
} from "@/components/im/CoordinatorRecords";
import { PageHeader } from "@/components/im/ui";
import { ChatWorkspace } from "@/components/im/ChatWorkspace";
import { NotificationList } from "@/components/im/NotificationList";
import { AllocationWorkspace } from "@/components/im/AllocationWorkspace";
import { CoordinatorAnalytics } from "@/components/im/CoordinatorAnalytics";
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
  const [importRevision, setImportRevision] = useState(0);
  useEffect(() => {
    const refresh = () => setImportRevision((value) => value + 1);
    const storage = (event: StorageEvent) => {
      if (event.key === "internmatch:records-imported") refresh();
    };
    window.addEventListener("internmatch:records-imported", refresh);
    window.addEventListener("storage", storage);
    return () => {
      window.removeEventListener("internmatch:records-imported", refresh);
      window.removeEventListener("storage", storage);
    };
  }, []);
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
      return <LiveDashboard key={importRevision} />;
    case "recommendations":
      return <CoordinatorRecommendations />;
    case "approvals":
      return <AllocationWorkspace key={importRevision} />;
    case "students":
      return <CoordinatorStudents />;
    case "opportunities":
      return <OpportunityWorkspace />;
    case "hosts":
      return <HostWorkspace />;
    case "progress":
      return <CoordinatorMonitoring key={`progress-${importRevision}`} mode="progress" />;
    case "requirements":
      return <RequirementWorkspace />;
    case "evaluations":
      return <CoordinatorMonitoring key="evaluations" mode="evaluations" />;
    case "monitoring-settings":
      return <ProgramMonitoringSettings />;
    case "analytics":
      return <CoordinatorAnalytics />;
    case "map":
      return (
        <>
          <PageHeader
            title="Accessibility map"
            subtitle="Placement distribution across host establishments in the Davao Region."
          />
          <AccessibilityMap
            key={importRevision}
            note="Use the map to balance placements against student travel burden."
          />
        </>
      );
    default:
      return <PageHeader title="Not found" subtitle="This coordinator page does not exist." />;
  }
}
