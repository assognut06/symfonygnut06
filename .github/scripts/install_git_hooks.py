"""Opt-in, repository-local hook installation; preserve existing hook setups."""
from pathlib import Path
import os
import subprocess
import sys


def install():
    root = Path(subprocess.check_output(['git', 'rev-parse', '--show-toplevel'], text=True).strip())
    os.chdir(root)
    result = subprocess.run(['git', 'config', '--get', 'core.hooksPath'], capture_output=True, text=True)
    current = result.stdout.strip()
    if result.returncode not in (0, 1):
        raise RuntimeError('Unable to inspect core.hooksPath.')
    if current and current != '.githooks':
        raise RuntimeError('An existing core.hooksPath is configured; integrate the hooks manually without overwriting it.')
    if not current:
        default = Path(subprocess.check_output(['git', 'rev-parse', '--git-path', 'hooks'], text=True).strip())
        active = [p.name for p in default.glob('*') if p.is_file() and not p.name.endswith('.sample') and os.access(p, os.X_OK)]
        if active:
            raise RuntimeError('Existing hooks would be hidden: ' + ', '.join(active))
    for name in ('commit-msg', 'pre-push'):
        hook = root / '.githooks' / name
        if not hook.is_file() or not os.access(hook, os.X_OK):
            raise RuntimeError(f'Missing or non-executable hook: {hook}')
    subprocess.run(['git', 'config', '--local', 'core.hooksPath', '.githooks'], check=True)
    print('Local hooks enabled for this clone (.githooks).')


if __name__ == '__main__':
    try:
        install()
    except (RuntimeError, subprocess.CalledProcessError) as exc:
        print(exc, file=sys.stderr)
        sys.exit(1)
