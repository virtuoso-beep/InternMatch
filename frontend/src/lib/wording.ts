/** Plain-language wording shared by several screens (no technical terms on normal screens). */

/** Help text for the similarity score (spec §8: raw score, never a percentage). */
export const SIMILARITY_HELP =
  "How closely the student's skills fit the tasks. Higher means a closer fit. Use it to compare items on this list. It isn't a percentage.";

/** How recommendations are ordered, by ranking mode. */
export const RANKING_LINE = {
  similarity: "Ranked by skills match and distance",
  learned: "Ranked by skills match, distance, and past coordinator judgments",
} as const;

export const TEST_MODE_LINE =
  "Test mode: rankings use practice data, not real coordinator judgments.";
