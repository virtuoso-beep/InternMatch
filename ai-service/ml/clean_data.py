import pandas as pd
import os
import argparse

def clean_data(input_path: str, output_path: str):
    if not os.path.exists(input_path):
        raise FileNotFoundError(f"Input file not found: {input_path}")
        
    df = pd.read_csv(input_path)
    
    # Require similarity_score, capacity_at_time, moa_active, label
    critical_cols = ['similarity_score', 'capacity_at_time', 'moa_active', 'label']
    df = df.dropna(subset=critical_cols)
    
    # Handle distance_km missing: create indicator and impute with median
    if df['distance_km'].isnull().any():
        df['distance_km_missing'] = df['distance_km'].isnull().astype(int)
        median_dist = df['distance_km'].median()
        if pd.isna(median_dist):
            median_dist = 0.0
        df['distance_km'] = df['distance_km'].fillna(median_dist)
    else:
        df['distance_km_missing'] = 0
        
    # Validate ranges
    df = df[
        (df['similarity_score'] >= -1.0) & (df['similarity_score'] <= 1.0) &
        (df['distance_km'] >= 0.0) &
        (df['capacity_at_time'] >= -100) & # allow negatives or whatever if valid, maybe >= 0
        (df['label'].isin([0, 1])) &
        (df['moa_active'].isin([0, 1]))
    ]
    
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    df.to_csv(output_path, index=False)
    print(f"Cleaned data saved to {output_path} (Rows: {len(df)})")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", default="../../backend/storage/app/ml/judgment_export.csv")
    parser.add_argument("--output", default="cleaned_judgments.csv")
    args = parser.parse_args()
    clean_data(args.input, args.output)
