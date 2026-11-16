"""Offline PR naming check; no API calls or repository mutations."""
import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

POLICY = Path(__file__).resolve().parents[1] / 'branch-policy.json'
BRANCH = re.compile(r'(feature|fix|hotfix|refactor|docs|chore|test)/(GNUT06-[1-9][0-9]*)-[a-z0-9]+(?:-[a-z0-9]+)*')


def check(pr, policy):
    name = pr['head']['ref']
    source_repo = (pr['head'].get('repo') or {}).get('full_name')
    created = datetime.fromisoformat(pr['created_at'].replace('Z', '+00:00'))
    cutoff = datetime.fromisoformat(policy['recorded_on']).replace(tzinfo=timezone.utc)
    if created < cutoff:
        return True, 'Historical PR: naming rules are not retroactive.'
    if source_repo == policy['repository'] and name in (
        policy['permanent_branches'] + policy['legacy_branches']
    ):
        return True, 'Existing or permanent repository branch: exempt.'
    match = BRANCH.fullmatch(name)
    if not match:
        return False, 'Expected <type>/GNUT06-<number>-<lowercase-ascii-description>.'
    key = match.group(2)
    text = (pr.get('title') or '') + '\n' + (pr.get('body') or '')
    if not re.search(r'(?<![A-Za-z0-9_])' + re.escape(key) + r'(?![A-Za-z0-9_])', text):
        return False, f'PR title or body must reference {key}.'
    return True, 'Branch name and PR Jira reference are valid.'


if __name__ == '__main__':
    event = json.loads(Path(sys.argv[1]).read_text())
    valid, message = check(event['pull_request'], json.loads(POLICY.read_text()))
    print(('OK: ' if valid else '::error::') + message)
    sys.exit(0 if valid else 1)
