import unittest
import main
from fastapi.testclient import TestClient

class ApiTests(unittest.TestCase):
    def setUp(self):
        self.client = TestClient(main.app)
    def test_health_is_not_ready_without_model(self):
        self.assertEqual(503, self.client.get('/health').status_code)
    def test_known_vectors_rank_and_reject_duplicates(self):
        left = [1.0]+[0.0]*767
        right = [0.0,1.0]+[0.0]*766
        data = {'student_vector':left, 'candidates':[{'id':2,'vector':right},{'id':1,'vector':left}]}
        result=self.client.post('/recommendations',json=data)
        self.assertEqual(200,result.status_code)
        self.assertEqual([1,2],[r['id'] for r in result.json()['data']])
        self.assertEqual([1.0,0.0],[r['similarity_score'] for r in result.json()['data']])
        data['candidates'][1]['id']=2
        self.assertEqual(422,self.client.post('/recommendations',json=data).status_code)
    def test_invalid_dimension_is_rejected(self):
        self.assertEqual(422,self.client.post('/recommendations',json={'student_vector':[1], 'candidates':[]}).status_code)

if __name__=='__main__':unittest.main()
