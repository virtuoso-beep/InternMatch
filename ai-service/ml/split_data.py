import pandas as pd
from sklearn.model_selection import train_test_split
import os
import argparse

def split_data(input_path: str, train_path: str, test_path: str, test_size: float = 0.2):
    if not os.path.exists(input_path):
        raise FileNotFoundError(f"Input file not found: {input_path}")
        
    df = pd.read_csv(input_path)
    if len(df) == 0:
        raise ValueError("Input data is empty.")
    
    # Check if we have enough data for split
    if len(df) < 2:
        print("Not enough data to split, duplicating for testing purposes.")
        df = pd.concat([df, df])
        
    train, test = train_test_split(df, test_size=test_size, stratify=df['label'], random_state=42)
    
    os.makedirs(os.path.dirname(train_path), exist_ok=True)
    os.makedirs(os.path.dirname(test_path), exist_ok=True)
    
    train.to_csv(train_path, index=False)
    test.to_csv(test_path, index=False)
    
    print(f"Data split successful:")
    print(f"Train size: {len(train)}")
    print(f"Test size: {len(test)}")
    print(f"Train class distribution:\n{train['label'].value_counts(normalize=True)}")
    print(f"Test class distribution:\n{test['label'].value_counts(normalize=True)}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", default="cleaned_judgments.csv")
    parser.add_argument("--train", default="train.csv")
    parser.add_argument("--test", default="test.csv")
    args = parser.parse_args()
    split_data(args.input, args.train, args.test)
