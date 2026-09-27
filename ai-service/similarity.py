import math


def cosine(left: list[float], right: list[float]) -> float:
    if not left or len(left) != len(right):
        raise ValueError("Vectors must be nonempty and have equal dimensions")
    if not all(math.isfinite(x) for x in left + right):
        raise ValueError("Vector values must be finite")
    a, b = math.sqrt(sum(x*x for x in left)), math.sqrt(sum(x*x for x in right))
    if not a or not b:
        raise ValueError("Zero vectors cannot be compared")
    return max(-1.0, min(1.0, sum(x*y for x, y in zip(left, right)) / (a*b)))
