import pandas as pd
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import StandardScaler
import joblib
import os
import argparse

try:
    from ml.model_card import FEATURES, write_train_meta
except ImportError:  # run as a script from ml/
    from model_card import FEATURES, write_train_meta

def train_model(train_path: str, model_path: str, scaler_path: str):
    df = pd.read_csv(train_path)
    
    features = FEATURES
    X = df[features].to_numpy(dtype=float)  # plain arrays, as at ranking time
    y = df['label']
    
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    model = LogisticRegression(class_weight='balanced', random_state=42)
    model.fit(X_scaled, y)
    
    os.makedirs(os.path.dirname(model_path), exist_ok=True)
    os.makedirs(os.path.dirname(scaler_path), exist_ok=True)
    
    joblib.dump(model, model_path)
    joblib.dump(scaler, scaler_path)
    
    write_train_meta(model_path, df)
    print("Model and scaler saved successfully.")
    print(f"Coefficients: {dict(zip(features, model.coef_[0]))}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--train", default="train.csv")
    parser.add_argument("--model", default="models/judgment_model.joblib")
    parser.add_argument("--scaler", default="models/scaler.joblib")
    args = parser.parse_args()
    train_model(args.train, args.model, args.scaler)
