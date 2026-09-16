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
        ],
      },
      {
        group: "Monitoring",
        items: [
          { label: "Internship Progress", section: "progress" },
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
      { group: "Overview", items: [{ label: "System Dashboard", section: "" }] },
      {
        group: "User Management",
        items: [
          { label: "User Accounts", section: "users" },
          { label: "Roles & Permissions", section: "roles" },
        ],
      },
      {
        group: "Records",
        items: [
          { label: "Host Establishments", section: "hosts" },
          { label: "Programs & Curriculum", section: "programs" },
          { label: "MOA Records", section: "moa" },
        ],
      },
      {
        group: "System",
        items: [
          { label: "Audit Trail", section: "audit" },
          { label: "Backup & Recovery", section: "backup" },
        ],
      },
    ],
  },
};

export const ROLE_ORDER: RoleKey[] = ["student", "coordinator", "supervisor", "dean", "admin"];

export type Host = {
  name: string;
  field: string;
  city: string;
  km: number;
  match: number;
  slotsOpen: number;
  slotsTotal: number;
  tags: string[];
  travel: string;
  rating: number;
  accessibility: "High" | "Moderate" | "Low";
  moa: string;
  x: number;
  y: number;
};

export const HOSTS: Host[] = [
  {
    name: "DataCore Solutions Inc.",
    field: "Software Development",
    city: "Tagum City",
    km: 3.2,
    match: 92,
    slotsOpen: 2,
    slotsTotal: 4,
    tags: ["Web Development", "Database Design", "REST APIs"],
    travel: "12 min",
    rating: 4.6,
    accessibility: "High",
    moa: "Active until Jun 2027",
    x: 46,
    y: 41,
  },
  {
    name: "Tagum City IT Office",
    field: "Systems Administration",
    city: "Tagum City",
    km: 1.8,
    match: 84,
    slotsOpen: 1,
    slotsTotal: 2,
    tags: ["Networking", "Technical Support"],
    travel: "9 min",
    rating: 4.3,
    accessibility: "High",
    moa: "Active until Mar 2027",
    x: 33,
    y: 53,
  },
  {
    name: "NorthLink Networks",
    field: "Networking & Support",
    city: "Davao City",
    km: 6.4,
    match: 79,
    slotsOpen: 3,
    slotsTotal: 3,
    tags: ["Networking"],
    travel: "26 min",
    rating: 4.1,
    accessibility: "Moderate",
    moa: "Expiring in 30 days",
    x: 59,
    y: 57,
  },
  {
    name: "Coastal Web Studio",
    field: "Web & Creative",
    city: "Panabo City",
    km: 11.6,
    match: 74,
    slotsOpen: 2,
    slotsTotal: 5,
    tags: ["Web Development", "UI Design"],
    travel: "38 min",
    rating: 3.9,
    accessibility: "Low",
    moa: "Active until Nov 2026",
    x: 72,
    y: 27,
  },
  {
    name: "Davao Region IT Hub",
    field: "Data & Analytics",
    city: "Davao City",
    km: 14.2,
    match: 90,
    slotsOpen: 4,
    slotsTotal: 6,
    tags: ["Data Analysis", "Python", "Reporting"],
    travel: "45 min",
    rating: 4.5,
    accessibility: "Low",
    moa: "Active until Aug 2027",
    x: 82,
    y: 66,
  },
];

export const PENDING_RECOMMENDATIONS = [
  { student: "Trisha Talamillo", program: "BSIT", host: "DataCore Solutions Inc.", match: 92, km: 3.2 },
  { student: "Twinkle Odruña", program: "BSIT", host: "Tagum City IT Office", match: 88, km: 1.8 },
  { student: "J. Ramos", program: "BSCS", host: "Coastal Web Studio", match: 74, km: 11.6 },
  { student: "M. Cruz", program: "BSIS", host: "Davao Region IT Hub", match: 90, km: 14.2 },
  { student: "K. Bautista", program: "BSIT", host: "NorthLink Networks", match: 81, km: 6.4 },
  { student: "P. Yap", program: "BSCS", host: "DataCore Solutions Inc.", match: 86, km: 4.9 },
];

export const REQUIREMENTS = [
  { name: "Medical Clearance", status: "Approved", date: "Jul 14, 2026" },
  { name: "Parental Consent", status: "Approved", date: "Jul 14, 2026" },
  { name: "Endorsement Letter", status: "Approved", date: "Jul 20, 2026" },
  { name: "Memorandum of Agreement", status: "Pending", date: "Submitted Aug 2, 2026" },
  { name: "Insurance Certificate", status: "Pending", date: "Submitted Aug 9, 2026" },
  { name: "Weekly Journal (Week 6)", status: "Missing", date: "Due Aug 30, 2026" },
];

export const COMPETENCIES = [
  { name: "Web Development", level: 82, source: "Course grades + portfolio" },
  { name: "Database Design", level: 76, source: "Course grades" },
  { name: "REST APIs", level: 68, source: "Self-assessment" },
  { name: "Networking", level: 54, source: "Course grades" },
  { name: "Technical Support", level: 49, source: "Self-assessment" },
  { name: "UI Design", level: 61, source: "Portfolio" },
];

export const INTERNS = [
  { name: "Trisha Talamillo", program: "BSIT", hours: 214, required: 486, status: "On track", rating: 4.6 },
  { name: "Twinkle Odruña", program: "BSIT", hours: 268, required: 486, status: "On track", rating: 4.4 },
  { name: "J. Ramos", program: "BSCS", hours: 128, required: 486, status: "At risk", rating: 3.8 },
  { name: "M. Cruz", program: "BSIS", hours: 402, required: 486, status: "Ahead", rating: 4.8 },
];

export const ATTENDANCE = [
  { date: "Aug 24, 2026", intern: "Trisha Talamillo", timeIn: "8:02 AM", timeOut: "5:04 PM", hours: 8, status: "Verified" },
  { date: "Aug 24, 2026", intern: "Twinkle Odruña", timeIn: "7:58 AM", timeOut: "5:00 PM", hours: 8, status: "Verified" },
  { date: "Aug 25, 2026", intern: "Trisha Talamillo", timeIn: "8:10 AM", timeOut: "5:02 PM", hours: 8, status: "Verified" },
  { date: "Aug 25, 2026", intern: "J. Ramos", timeIn: "9:15 AM", timeOut: "3:00 PM", hours: 5, status: "Flagged" },
  { date: "Aug 26, 2026", intern: "M. Cruz", timeIn: "8:00 AM", timeOut: "5:30 PM", hours: 8.5, status: "Pending" },
];

export const AUDIT = [
  { time: "Aug 27, 2026 08:41", actor: "Angeli Pancho", action: "Approved placement", target: "Trisha Talamillo → DataCore Solutions Inc.", ip: "10.0.4.21" },
  { time: "Aug 27, 2026 08:12", actor: "System", action: "Recommendation batch generated", target: "160 students · model v1.4", ip: "internal" },
  { time: "Aug 26, 2026 16:35", actor: "Rico Fernandez", action: "Submitted evaluation", target: "Twinkle Odruña · Midterm", ip: "112.198.4.8" },
  { time: "Aug 26, 2026 11:02", actor: "System Administrator", action: "Created user account", target: "L. Uy · Host Supervisor", ip: "10.0.4.2" },
  { time: "Aug 25, 2026 09:20", actor: "Dr. R. Villanueva", action: "Exported report", target: "Accreditation summary AY 2025-2026", ip: "10.0.4.9" },
];

export const PROGRAMS = [
  { code: "BSIT", name: "BS Information Technology", students: 96, deployed: 89, partners: 24, rate: 93 },
  { code: "BSCS", name: "BS Computer Science", students: 38, deployed: 34, partners: 15, rate: 89 },
  { code: "BSIS", name: "BS Information Systems", students: 26, deployed: 25, partners: 11, rate: 96 },
];

export const USERS = [
  { name: "Angeli Pancho", role: "Practicum Coordinator", login: "Today, 9:14 AM", status: "Active" },
  { name: "Dr. R. Villanueva", role: "Dean", login: "Aug 5, 3:40 PM", status: "Active" },
  { name: "Rico Fernandez", role: "Host Supervisor", login: "Today, 10:22 AM", status: "Active" },
  { name: "Trisha Talamillo", role: "Student Intern", login: "Today, 8:02 AM", status: "Active" },
  { name: "J. Delos Santos", role: "Student Intern", login: "Jul 12, 2026", status: "Disabled" },
  { name: "L. Uy", role: "Host Supervisor", login: "Never logged in", status: "Pending" },
];

export const EVALUATIONS = [
  { intern: "Trisha Talamillo", period: "Midterm", technical: 4.6, work: 4.7, communication: 4.4, overall: 4.6, status: "Submitted" },
  { intern: "Twinkle Odruña", period: "Midterm", technical: 4.3, work: 4.5, communication: 4.6, overall: 4.4, status: "Submitted" },
  { intern: "J. Ramos", period: "Midterm", technical: 3.6, work: 3.8, communication: 4.0, overall: 3.8, status: "Draft" },
  { intern: "M. Cruz", period: "Final", technical: 4.9, work: 4.8, communication: 4.7, overall: 4.8, status: "Submitted" },
];

export const OPPORTUNITIES = [
  { title: "Web Development Intern", slots: "2 of 4 open", competencies: ["Web Development", "REST APIs"], status: "Published" },
  { title: "Database Support Intern", slots: "1 of 2 open", competencies: ["Database Design", "SQL"], status: "Published" },
  { title: "QA Testing Intern", slots: "0 of 2 open", competencies: ["Testing", "Documentation"], status: "Closed" },
];

export const STUDENTS = [
  { name: "Trisha Talamillo", program: "BSIT", completeness: 86, status: "Deployed", host: "DataCore Solutions Inc." },
  { name: "Twinkle Odruña", program: "BSIT", completeness: 92, status: "Deployed", host: "Tagum City IT Office" },
  { name: "J. Ramos", program: "BSCS", completeness: 64, status: "Pending approval", host: "Coastal Web Studio" },
  { name: "M. Cruz", program: "BSIS", completeness: 100, status: "Deployed", host: "Davao Region IT Hub" },
  { name: "K. Bautista", program: "BSIT", completeness: 58, status: "Unplaced", host: "—" },
  { name: "P. Yap", program: "BSCS", completeness: 71, status: "Pending approval", host: "DataCore Solutions Inc." },
];
