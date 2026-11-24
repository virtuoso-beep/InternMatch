"""Docker backups and isolated recovery drills; never restores the live database."""
import argparse, datetime as dt, gzip, hashlib, json, os, re, subprocess, tarfile, uuid
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
STORE = ROOT / 'backend/storage/app/backups'

def compose(*args, **kwargs):
    files = ['-f', str(ROOT / 'docker-compose.yml')]
    if os.environ.get('INTERNMATCH_PRODUCTION') == '1':
        files += ['-f', str(ROOT / 'docker-compose.production.yml')]
    return subprocess.run(['docker', 'compose', *files, *args], cwd=ROOT, check=True, **kwargs)

def digest(path):
    with path.open('rb') as stream:
        return hashlib.file_digest(stream, 'sha256').hexdigest()

def manifest(folder, record):
    temp = folder / 'manifest.tmp'
    temp.write_text(json.dumps(record, indent=2), encoding='utf-8')
    temp.replace(folder / 'manifest.json')

def backup():
    STORE.mkdir(parents=True, exist_ok=True)
    stamp = dt.datetime.now(dt.timezone.utc).strftime('%Y%m%dT%H%M%SZ') + '-' + uuid.uuid4().hex[:8]
    folder = STORE / stamp
    folder.mkdir(mode=0o700)
    record = dict(id=stamp, created_at=dt.datetime.now(dt.timezone.utc).isoformat(), status='running', verified_at=None, files=[])
    manifest(folder, record)
    try:
        with (folder / 'database.sql.gz').open('wb') as target:
            compose('exec', '-T', 'mysql', 'sh', '-c', 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot --single-transaction --quick --routines --events --triggers --no-tablespaces --set-gtid-purged=OFF "$MYSQL_DATABASE" | gzip', stdout=target)
        with gzip.open(folder / 'database.sql.gz', 'rb') as source:
            tail = b''
            for chunk in iter(lambda: source.read(1024*1024), b''):
                tail = (tail + chunk)[-4096:]
            if b'Dump completed' not in tail:
                raise RuntimeError('Database dump did not complete')
        with (folder / 'documents.tar.gz').open('wb') as target:
            compose('exec', '-T', 'backend', 'tar', '-czf', '-', '-C', '/var/www/html/storage/app', 'private', 'public', stdout=target)
        with tarfile.open(folder / 'documents.tar.gz', 'r:gz') as archive:
            for member in archive:
                if not (member.isfile() or member.isdir()):
                    raise RuntimeError('Document archive contains unsupported links')
        record['files'] = [dict(name=name, bytes=(folder/name).stat().st_size, sha256=digest(folder/name)) for name in ['database.sql.gz', 'documents.tar.gz']]
        record['status'] = 'completed'
        record['consistency'] = 'Sequential database snapshot and document archive; use a production maintenance window for coordinated consistency.'
        manifest(folder, record)
        print('Backup completed:', stamp)
        prune(int(os.environ.get('INTERNMATCH_BACKUP_KEEP', '14')))
    except Exception:
        record['status'] = 'failed'
        manifest(folder, record)
        raise

BACKUP_ID = re.compile(r'\d{8}T\d{6}Z-[a-f0-9]{8}')

def prune(keep):
    """Keep the newest `keep` completed backups; delete older backups (completed or failed). Runs only after a
    successful backup, never deletes the newest completed ones, and touches only folders named like a backup."""
    import shutil
    if keep < 1:
        raise ValueError('INTERNMATCH_BACKUP_KEEP must be at least 1')
    folders = sorted((p for p in STORE.iterdir() if p.is_dir() and BACKUP_ID.fullmatch(p.name)), key=lambda p: p.name, reverse=True)
    def status(folder):
        try:
            return json.loads((folder / 'manifest.json').read_text()).get('status')
        except (OSError, ValueError):
            return None
    completed = [p for p in folders if status(p) == 'completed']
    if not completed:
        return []
    kept = set(completed[:keep])
    oldest_kept = min(p.name for p in kept)
    removed = [p for p in folders if p not in kept and p.name < oldest_kept]
    for folder in removed:
        shutil.rmtree(folder)
    if removed:
        print('Removed old backups:', ', '.join(p.name for p in removed))
    return [p.name for p in removed]

def verify(identifier):
    if not re.fullmatch(r'\d{8}T\d{6}Z-[a-f0-9]{8}', identifier):
        raise ValueError('Invalid backup identifier')
    folder = STORE / identifier
    record = json.loads((folder/'manifest.json').read_text())
    if record['status'] != 'completed' or {x['name'] for x in record['files']} != {'database.sql.gz', 'documents.tar.gz'}:
        raise ValueError('Incomplete backup')
    for item in record['files']:
        if digest(folder/item['name']) != item['sha256']:
            raise ValueError('Backup checksum mismatch')
    database = 'internmatch_recovery_' + uuid.uuid4().hex[:12] + '_testing'
    compose('exec', '-T', 'mysql', 'sh', '-c', 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "CREATE DATABASE ' + database + '"')
    # Stream through a temporary plain SQL file rather than keeping the database in memory.
    import tempfile, shutil
    with tempfile.TemporaryFile() as plain:
        with gzip.open(folder/'database.sql.gz', 'rb') as source:
            shutil.copyfileobj(source, plain)
        plain.seek(0)
        compose('exec', '-T', 'mysql', 'sh', '-c', 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot ' + database, stdin=plain)
    result = compose('exec', '-T', 'mysql', 'sh', '-c', 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\'' + database + '\'"', capture_output=True)
    count = int(result.stdout.strip())
    if count == 0:
        raise RuntimeError('Restored database is empty')
    destination = folder / ('verification-' + database)
    destination.mkdir()
    with tarfile.open(folder/'documents.tar.gz', 'r:gz') as archive:
        for member in archive:
            if not (member.isfile() or member.isdir()) or not (destination/member.name).resolve().is_relative_to(destination.resolve()):
                raise ValueError('Unsafe archive entry')
        archive.extractall(destination, filter='data')
    record['verified_at'] = dt.datetime.now(dt.timezone.utc).isoformat()
    record['verification'] = dict(database=database, tables=count, scope='Checksums, SQL import and document extraction; application acceptance remains an operator check.')
    manifest(folder, record)
    print('Verified isolated restore:', identifier, 'tables:', count)
    print('Retained recovery database:', database)

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('action', choices=['backup', 'verify'])
    parser.add_argument('identifier', nargs='?')
    args = parser.parse_args()
    backup() if args.action == 'backup' else verify(args.identifier or '')
