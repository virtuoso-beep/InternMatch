# Recommendation learning (supervised reranking)

Spec §9 and Development Guide Phase 9. Logistic Regression is used only when the documented rule is met;
otherwise recommendations use similarity, then distance.

## How a model is trained and installed

Run it from InternMatch, not by hand:

- Administrator: **System Settings → Recommendation learning → Update recommendations**
- Command line: `php artisan ml:retrain`

Laravel collects the approved coordinator judgments (suitable / not suitable; uncertain is excluded), checks
that there are at least **30 suitable and 30 not-suitable** labels, and sends them to the AI service
(`POST /ml/train`). The AI service (`ml/learning.py`):

1. uses three features: similarity score, straight-line distance and program (one shared model);
2. evaluates with **leave-one-student-out** cross-validation and reports accuracy, precision, recall and F1,
   overall and per program;
3. installs the model only if its leave-one-student-out F1 is **better** than a model using similarity
   alone, and every program in the data has both suitable and not-suitable labels;
4. evaluates the fallback ranking with Precision@3, Recall@3 and NDCG@3 against keyword-overlap and
   distance-only baselines.

The model is written to `ML_MODEL_DIR` (the `ai_models` volume, `/models/ranker`), swapped in atomically and
reloaded without restarting the service or rebuilding the image. If the rule is not met or the model does not
pass, the current model stays. Every run is recorded in Laravel (`ranker_runs`).

## Test data

Synthetic labels are test data. They are marked on the model card, never count toward the 30/30 rule for
real judgments, and a model built from them is used only when `ML_ALLOW_SYNTHETIC_MODEL=1`, which is set in
both `docker-compose.override.yml` (local) and `docker-compose.production.yml`. While such a model ranks,
screens say "Test mode: rankings use practice data, not real coordinator judgments."
`php artisan ml:retrain --test-data` (or "Use practice data" on the admin card) uses every labelled synthetic
record that can be scored, with the same 30/30 rule and the same install check.

Test rows are scored the way live ranking scores, so the model learns on the scale it ranks with: similarity
is the cosine of the stored E5 embeddings of the student and the opportunity, distance is the straight-line
distance from the student's profile to the host, and keyword overlap is the share of the opportunity's
competencies the student lists. The source file's own `skill_match_score` and `distance_km` are a different
measure (0 to 1 skill overlap, not E5 cosine) and are not used. Labels whose opportunity was not imported, or
that have no current embedding, are skipped and the command reports how many.

## Research export

`php artisan ml:export-judgments` writes the labelled pairs to `backend/storage/app/private/ml/` as CSV for
analysis outside the system.
