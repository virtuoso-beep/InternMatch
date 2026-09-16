<?php

namespace App;

enum Role: string
{
    case Student = 'student';
    case Coordinator = 'coordinator';
    case Supervisor = 'supervisor';
    case Dean = 'dean';
    case Admin = 'admin';

    /** @return list<Permission> */
    public function permissions(): array
    {
        $common = [Permission::ManageOwnProfile];

        return array_merge($common, match ($this) {
            self::Student => [Permission::ManageOwnCompetencies, Permission::SubmitOwnRequirements, Permission::ViewOwnPlacement],
            self::Coordinator => [Permission::ViewProgramRecords, Permission::ViewReports, Permission::ViewAudit, Permission::DecidePlacements, Permission::ReviewRequirements, Permission::ManageHosts],
            self::Supervisor => [Permission::ManageAssignedHost, Permission::MonitorAssignedInterns, Permission::SubmitEvaluations],
            self::Dean => [Permission::ViewProgramRecords, Permission::ViewReports, Permission::ViewAudit],
            self::Admin => [Permission::ManageSystem, Permission::ManageAccounts, Permission::ManageAcademicRecords, Permission::ManageHosts, Permission::ViewAudit],
        });
    }

    public function allows(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
