import pandas as pd
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler
import joblib
import os
import argparse

def train_model(train_path: str, model_path: str, scaler_path: str):
    df = pd.read_csv(train_path)
    
    features = ['similarity_score', 'distance_km', 'capacity_at_time', 'moa_active', 'distance_km_missing']
    X = df[features]
    y = df['label']
    
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    model = LogisticRegression(class_weight='balanced', random_state=42)
    model.fit(X_scaled, y)
    
    os.makedirs(os.path.dirname(model_path), exist_ok=True)
    os.makedirs(os.path.dirname(scaler_path), exist_ok=True)
    
    joblib.dump(model, model_path)
    joblib.dump(scaler, scaler_path)
    
    print("Model and scaler saved successfully.")
    print(f"Coefficients: {dict(zip(features, model.coef_[0]))}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--train", default="train.csv")
    parser.add_argument("--model", default="models/judgment_model.joblib")
    parser.add_argument("--scaler", default="models/scaler.joblib")
    args = parser.parse_args()
    train_model(args.train, args.model, args.scaler)
