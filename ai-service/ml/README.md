# ML Ranking Pipeline

This directory contains the ML training pipeline for re-ranking InternMatch recommendations. 
It requires real approved coordinator judgments to produce a valid model.

## Requirements
- Python 3.10+
- `scikit-learn` and `joblib` (added to `requirements.txt`)
- A CSV file of approved judgments.

## Pipeline Steps

1. **Export Judgments**: Run the Laravel command to export data.
   ```bash
   php artisan ml:export-judgments
   ```
   This exports to `storage/app/ml/judgment_export.csv`.

2. **Clean Data**: Validates ranges and handles missing distances.
   ```bash
   python ml/clean_data.py
   ```

3. **Split Data**: Stratified 80/20 train/test split.
   ```bash
   python ml/split_data.py
   ```

4. **Train Model**: Trains a LogisticRegression model using standardized features.
   ```bash
   python ml/train_model.py
   ```

5. **Evaluate Model**: Checks F1 score threshold and generates a report.
   ```bash
   python ml/evaluate_model.py
   ```

Or you can run the full orchestrator:
```bash
python ml/pipeline.py
```

## Integration
The trained model is placed in `ml/models/`. The `/recommendations/ml` endpoint automatically activates and uses the model if the `.joblib` files are present. Otherwise, it falls back to cosine similarity.

## Note
To actually use the model in production, ensure `judgment_export.csv` has sufficient real coordinator data.
