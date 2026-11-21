import pandas as pd
import joblib
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, confusion_matrix, roc_auc_score
import json
import os
import argparse

try:
    from ml.model_card import FEATURES, remove_card, write_card
except ImportError:  # run as a script from ml/
    from model_card import FEATURES, remove_card, write_card

def evaluate_model(test_path: str, model_path: str, scaler_path: str, report_path: str, threshold: float = 0.7):
    df = pd.read_csv(test_path)
    
    features = FEATURES
    X = df[features].to_numpy(dtype=float)  # plain arrays, as at ranking time
    y = df['label']
    
    model = joblib.load(model_path)
    scaler = joblib.load(scaler_path)
    
    X_scaled = scaler.transform(X)
    y_pred = model.predict(X_scaled)
    y_prob = model.predict_proba(X_scaled)[:, 1]
    
    # Handle single class in test set
    if len(set(y)) == 1:
        roc_auc = 0.0
    else:
        roc_auc = roc_auc_score(y, y_prob)

    metrics = {
        'accuracy': float(accuracy_score(y, y_pred)),
        'precision': float(precision_score(y, y_pred, zero_division=0)),
        'recall': float(recall_score(y, y_pred, zero_division=0)),
        'f1': float(f1_score(y, y_pred, zero_division=0)),
        'roc_auc': float(roc_auc),
        'confusion_matrix': confusion_matrix(y, y_pred).tolist()
    }
    
    os.makedirs(os.path.dirname(report_path), exist_ok=True)
    with open(report_path, 'w') as f:
        json.dump(metrics, f, indent=4)
        
    print(f"Evaluation report saved to {report_path}")
    print(f"Metrics: {metrics}")
    
    if metrics['f1'] < threshold:
        # A failed model must never be activated by an older passing card.
        remove_card(model_path)
        raise ValueError(f"Model failed to meet F1 threshold {threshold}. Current F1: {metrics['f1']}")
    write_card(model_path, scaler_path, metrics, threshold, df)
    print(f"Model passed threshold checks (F1 >= {threshold}). Model card written.")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--test", default="test.csv")
    parser.add_argument("--model", default="models/judgment_model.joblib")
    parser.add_argument("--scaler", default="models/scaler.joblib")
    parser.add_argument("--report", default="reports/evaluation_report.json")
    parser.add_argument("--threshold", type=float, default=0.7)
    args = parser.parse_args()
    evaluate_model(args.test, args.model, args.scaler, args.report, args.threshold)
