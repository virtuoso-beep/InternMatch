import unittest
from similarity import cosine

class SimilarityTests(unittest.TestCase):
    def test_known_vectors(self):
        self.assertAlmostEqual(1, cosine([1, 2, 3], [1, 2, 3]))
        self.assertAlmostEqual(0, cosine([1, 0], [0, 1]))
        self.assertAlmostEqual(-1, cosine([1, 0], [-1, 0]))
        self.assertAlmostEqual(1, cosine([2, 0], [20, 0]))
    def test_invalid_vectors(self):
        for a,b in [([],[]), ([0,0],[1,0]), ([1],[1,2]), ([float('nan')],[1]),([float('inf')],[1])]:
            with self.assertRaises(ValueError): cosine(a,b)

if __name__ == '__main__': unittest.main()
