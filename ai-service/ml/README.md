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
`docker-compose.override.yml` for local testing and is never loaded in production. Locally:
`php artisan ml:retrain --test-data` (up to 60 test labels per program, 30 of each, so the same 30/30 rule applies).

## Research export

`php artisan ml:export-judgments` writes the labelled pairs to `backend/storage/app/private/ml/` as CSV for
analysis outside the system.
