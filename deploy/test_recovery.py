import json
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch
import recovery


class RecoveryGuards(unittest.TestCase):
    def test_invalid_identifier_never_calls_docker(self):
        with patch.object(recovery, 'compose') as docker:
            with self.assertRaises(ValueError):
                recovery.verify('../live')
            docker.assert_not_called()

    def test_corrupt_archive_never_starts_restore(self):
        with tempfile.TemporaryDirectory() as temp, patch.object(recovery, 'STORE', Path(temp)), patch.object(recovery, 'compose') as docker:
            identifier = '20261026T000000Z-12345678'
            folder = Path(temp) / identifier
            folder.mkdir()
            (folder / 'database.sql.gz').write_bytes(b'corrupted')
            (folder / 'manifest.json').write_text(json.dumps({'status': 'completed', 'files': [
                {'name': 'database.sql.gz', 'sha256': 'invalid'},
                {'name': 'documents.tar.gz', 'sha256': 'invalid'},
            ]}))
            with self.assertRaises(ValueError):
                recovery.verify(identifier)
            docker.assert_not_called()


if __name__ == '__main__':
    unittest.main()
