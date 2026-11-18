export type RoleKey = "student" | "coordinator" | "supervisor" | "dean" | "admin";

export type NavItem = { label: string; section: string };
export type NavGroup = { group: string; items: NavItem[] };

export type RoleConfig = {
  key: RoleKey;
  title: string;
  userName: string;
  userLabel: string;
  initials: string;
  base: string;
  nav: NavGroup[];
};

export const ROLES: Record<RoleKey, RoleConfig> = {
  student: {
    key: "student",
    title: "Student Intern",
    userName: "Trisha Talamillo",
    userLabel: "Student Intern",
    initials: "TT",
    base: "/student",
    nav: [
      {
        group: "Main",
        items: [
          { label: "Dashboard", section: "" },

          { label: "Competencies", section: "competencies" },
          { label: "Opportunities", section: "opportunities" },
          { label: "Recommendations", section: "recommendations" },
        ],
      },
      {
        group: "Internship",
        items: [
          { label: "My Internship", section: "internship" },
          { label: "Requirements", section: "requirements" },
          { label: "Accessibility Map", section: "map" },
        ],
      },
    ],
  },
  coordinator: {
    key: "coordinator",
    title: "Practicum Coordinator",
    userName: "Angeli Pancho",
    userLabel: "Practicum Coordinator",
    initials: "AP",
    base: "/coordinator",
    nav: [
      { group: "Overview", items: [{ label: "Dashboard", section: "" }] },
      {
        group: "Placement",
        items: [
          { label: "Recommendations", section: "recommendations" },
          { label: "Pending Approvals", section: "approvals" },
          { label: "Student Profiles", section: "students" },
          { label: "Host Establishments", section: "hosts" },
          { label: "Agreements", section: "moa" },
          { label: "Opportunities", section: "opportunities" },
        ],
      },
      {
        group: "Monitoring",
        items: [
          { label: "Internship Progress", section: "progress" },
          { label: "Monitoring Settings", section: "monitoring-settings" },
          { label: "Requirements", section: "requirements" },
          { label: "Evaluations", section: "evaluations" },
        ],
      },
      {
        group: "Insights",
        items: [
          { label: "Analytics", section: "analytics" },
          { label: "Accessibility Map", section: "map" },
        ],
      },
    ],
  },
  supervisor: {
    key: "supervisor",
    title: "Host Establishment Supervisor",
    userName: "Rico Fernandez",
    userLabel: "Host Supervisor · DataCore Solutions Inc.",
    initials: "RF",
    base: "/supervisor",
    nav: [
      { group: "Overview", items: [{ label: "Dashboard", section: "" }] },
      {
        group: "Interns",
        items: [
          { label: "Assigned Interns", section: "interns" },
          { label: "Attendance & Hours", section: "attendance" },
          { label: "Evaluations", section: "evaluations" },
        ],
      },
      {
        group: "Establishment",
        items: [
          { label: "Company Profile", section: "company" },
          { label: "Internship Opportunities", section: "opportunities" },
          { label: "Memorandum of Agreement", section: "moa" },
        ],
      },
    ],
  },
  dean: {
    key: "dean",
    title: "Program Chair / Dean",
    userName: "Dr. R. Villanueva",
    userLabel: "Dean, College of Computing Education",
    initials: "RV",
    base: "/dean",
    nav: [
      { group: "Overview", items: [{ label: "Executive Dashboard", section: "" }] },
      {
        group: "Programs",
        items: [
          { label: "Program Performance", section: "performance" },
          { label: "Host Establishments", section: "hosts" },
          { label: "Placement Equity", section: "equity" },
        ],
      },
      {
        group: "Reporting",
        items: [
          { label: "Analytics", section: "analytics" },
          { label: "Accreditation Reports", section: "accreditation" },
          { label: "Export Data", section: "export" },
          { label: "Audit Trail", section: "audit" },
        ],
      },
    ],
  },
  admin: {
    key: "admin",
    title: "System Administrator",
    userName: "System Administrator",
    userLabel: "System Administrator",
    initials: "SA",
    base: "/admin",
    nav: [
      { group: "Dashboard", items: [{ label: "Dashboard", section: "" }] },
      {
        group: "User Management",
        items: [
          { label: "Students", section: "students" },
          { label: "User Accounts", section: "users" },
          { label: "Roles & Permissions", section: "roles" },
          { label: "Student Import", section: "student-import" },
        ],
      },
      {
        group: "Internship Management",
        items: [
          { label: "Host Establishments", section: "hosts" },
          { label: "Opportunities", section: "opportunities" },
          { label: "MOA Records", section: "moa" },
        ],
      },
      {
        group: "Academic Management",
        items: [
          { label: "Programs & Curriculum", section: "programs" },
          { label: "Monitoring Settings", section: "monitoring-settings" },
          { label: "Academic Terms", section: "terms" },
        ],
      },
      {
        group: "Communication & Alerts",
        items: [
          { label: "Chat Availability", section: "chat-settings" },
          { label: "Notifications", section: "notifications" },
        ],
      },
      {
        group: "System Administration",
        items: [
          { label: "Audit Trail", section: "audit" },
          { label: "Backup & Recovery", section: "backup" },
          { label: "System Settings", section: "settings" },
        ],
      },
    ],
  },
};

export const ROLE_ORDER: RoleKey[] = ["student", "coordinator", "supervisor", "dean", "admin"];
