import subprocess
import os

def run_pipeline():
    base_dir = os.path.dirname(__file__)
    clean_py = os.path.join(base_dir, "clean_data.py")
    split_py = os.path.join(base_dir, "split_data.py")
    train_py = os.path.join(base_dir, "train_model.py")
    eval_py = os.path.join(base_dir, "evaluate_model.py")

    try:
        print("--- Running Data Cleaning ---")
        subprocess.run(["python", clean_py], check=True)
        
        print("--- Running Data Split ---")
        subprocess.run(["python", split_py], check=True)
        
        print("--- Running Model Training ---")
        subprocess.run(["python", train_py], check=True)
        
        print("--- Running Model Evaluation ---")
        subprocess.run(["python", eval_py], check=True)
        
        print("--- Pipeline Completed Successfully ---")
    except subprocess.CalledProcessError as e:
        print(f"Pipeline failed at step: {e.cmd}")
        exit(1)

if __name__ == "__main__":
    run_pipeline()
