import { PageHeader } from "@/components/im/ui";
import { AccessibilityMap } from "@/components/im/AccessibilityMap";
import { ProfileEditor } from "@/components/im/ProfileEditor";
import { StudentOverview } from "@/components/im/StudentOverview";
import { StudentCompetencies } from "@/components/im/StudentCompetencies";
import { RequirementWorkspace } from "@/components/im/RequirementWorkspace";
import { NotificationList } from "@/components/im/NotificationList";
import { MonitoringWorkspace } from "@/components/im/MonitoringWorkspace";

export function StudentSection({ section }: { section: string }) {
  switch (section) {
    case "": return <StudentOverview />;
    case "internship": return <MonitoringWorkspace />;
    case "profile": return <ProfileEditor role="student" />;
    case "competencies": return <StudentCompetencies />;
    case "opportunities": return <StudentOverview opportunities />;
    case "recommendations": return <StudentOverview recommendations />;
    case "requirements": return <RequirementWorkspace />;
    case "notifications": return <NotificationList />;
    case "map": return <><PageHeader title="Accessibility map" subtitle="Saved locations and straight-line student-to-host distances." /><AccessibilityMap /></>;
    default: return <PageHeader title="Not found" subtitle="This student page does not exist." />;
  }
}
