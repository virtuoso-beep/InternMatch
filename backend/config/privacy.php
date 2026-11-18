<?php

/*
 * Privacy notice (spec §17B, Republic Act No. 10173 — Data Privacy Act of 2012).
 * Bump `version` whenever the notice text changes: every user is asked to acknowledge the new version.
 * The data protection officer contact and the retention periods are NOT yet confirmed by UMTC (spec §22),
 * so they are shown as clearly marked placeholders/proposals and must not be replaced with invented values.
 */
return [
    'version' => 'v1-2026-11-17',
    'title' => 'InternMatch privacy notice',
    'sections' => [
        ['heading' => 'What we collect', 'body' => 'Your name, contact details, student or staff identifier, program and enrollment records, competency profile, home locality and map coordinates, requirement documents, internship hours, journals, evaluations, and messages you send in InternMatch.'],
        ['heading' => 'Why we collect it', 'body' => 'To support internship placement, recommendations, cohort allocation, monitoring of hours and requirements, supervisor evaluations, and program reports at UM Tagum College. Your location is used only to compute straight-line distance for placement; your exact coordinates are never shown to host supervisors.'],
        ['heading' => 'Who can see it', 'body' => 'Only the people whose role needs it: your program\'s practicum coordinators, your assigned host supervisor (once you are deployed), and authorized program chairs or deans for program-level summaries. Administrators manage accounts but cannot read private conversations.'],
        ['heading' => 'How long we keep it', 'body' => 'Proposed periods, pending UMTC approval: chat messages 1 year after deployment ends; requirement documents 1 year after the internship term ends; placement, hours and evaluation records 5 years after you complete the internship; audit logs 3 years; backups roll over every 90 days. Until UMTC approves these periods, nothing is deleted automatically.'],
        ['heading' => 'Your rights and how to contact us', 'body' => 'You may request access to or correction of your personal data through an authorized administrator. Data protection officer: [CONTACT TO BE PROVIDED BY UM TAGUM COLLEGE — not yet confirmed].'],
    ],
];
