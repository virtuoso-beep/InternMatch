export type PlacementReference = {
  hosts: { id: number; name: string }[];
  programs: { id: number; code: string; name: string; required_ojt_hours: number | null }[];
  terms: { id: number; code: string; name: string; starts_on: string; ends_on: string }[];
  competencies: { id: number; name: string }[];
};
