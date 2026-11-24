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

    def test_prune_keeps_the_newest_completed_backups_and_only_backup_folders(self):
        with tempfile.TemporaryDirectory() as temp, patch.object(recovery, 'STORE', Path(temp)):
            def make(name, status):
                (Path(temp) / name).mkdir()
                (Path(temp) / name / 'manifest.json').write_text(json.dumps({'status': status}))
            make('20261101T020000Z-aaaaaaaa', 'completed')
            make('20261102T020000Z-bbbbbbbb', 'failed')
            make('20261103T020000Z-cccccccc', 'completed')
            make('20261104T020000Z-dddddddd', 'completed')
            make('20261105T020000Z-eeeeeeee', 'failed')   # newer than the kept ones: left for inspection
            (Path(temp) / 'notes').mkdir()                 # not a backup folder: never touched
            removed = recovery.prune(2)
            self.assertEqual(sorted(removed), ['20261101T020000Z-aaaaaaaa', '20261102T020000Z-bbbbbbbb'])
            self.assertEqual(sorted(p.name for p in Path(temp).iterdir()),
                             ['20261103T020000Z-cccccccc', '20261104T020000Z-dddddddd', '20261105T020000Z-eeeeeeee', 'notes'])
            with self.assertRaises(ValueError):
                recovery.prune(0)


if __name__ == '__main__':
    unittest.main()
