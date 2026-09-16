<?php

namespace App;

enum Permission: string
{
    case ManageSystem = 'manage_system';
    case ManageAccounts = 'manage_accounts';
    case ManageAcademicRecords = 'manage_academic_records';
    case ManageHosts = 'manage_hosts';
    case ViewProgramRecords = 'view_program_records';
    case ViewReports = 'view_reports';
    case ViewAudit = 'view_audit';
    case DecidePlacements = 'decide_placements';
    case ReviewRequirements = 'review_requirements';
    case ManageOwnProfile = 'manage_own_profile';
    case ManageOwnCompetencies = 'manage_own_competencies';
    case SubmitOwnRequirements = 'submit_own_requirements';
    case ViewOwnPlacement = 'view_own_placement';
    case ManageAssignedHost = 'manage_assigned_host';
    case MonitorAssignedInterns = 'monitor_assigned_interns';
    case SubmitEvaluations = 'submit_evaluations';
}
