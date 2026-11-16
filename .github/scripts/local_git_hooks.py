"""Local commit and push checks sharing the PR branch policy."""
import json
import re
import subprocess
import sys
from pathlib import Path

from check_branch_policy import BRANCH, POLICY

COMMIT = re.compile(
    r'(feat|fix|docs|style|refactor|perf|test|build|ci|chore|revert)'
    r'(?:\([a-z0-9]+(?:[-/][a-z0-9]+)*\))?: '
    r'\S(?:.*\S)? \[(GNUT06-[1-9][0-9]*)\]'
)


def git(*args):
    return subprocess.check_output(['git', *args], text=True).strip()


def branch_valid(name, policy):
    return name in policy['permanent_branches'] + policy['legacy_branches'] or bool(BRANCH.fullmatch(name))


def commit_message(message_path, policy):
    branch = git('symbolic-ref', '--quiet', '--short', 'HEAD')
    if not branch_valid(branch, policy):
        raise ValueError('Invalid branch name. Use <type>/GNUT06-<number>-<description>.')
    if branch in policy['legacy_branches']:
        return
    if Path(git('rev-parse', '--git-path', 'MERGE_HEAD')).exists():
        return  # Actual merges only, not arbitrary messages beginning with "Merge".
    # Apply Git's comment/whitespace cleanup before checking the subject.
    message = Path(message_path).read_bytes()
    cleaned = subprocess.check_output(['git', 'stripspace', '--strip-comments'], input=message).decode('utf-8')
    subject = cleaned.splitlines()[0] if cleaned else ''
    match = COMMIT.fullmatch(subject)
    if not match:
        raise ValueError('Expected <type>(<optional-scope>): <description> [GNUT06-<number>].')
    branch_match = BRANCH.fullmatch(branch)
    if branch_match and branch_match.group(2) != match.group(2):
        raise ValueError('Commit Jira key must match the branch Jira key.')


def pre_push(lines, policy):
    for line in lines:
        local_ref, local_sha, remote_ref, _ = line.split()
        if set(local_sha) == {'0'} or not remote_ref.startswith('refs/heads/'):
            continue  # Deletions and tags do not introduce work branches.
        refs = [remote_ref]
        if local_ref.startswith('refs/heads/'):
            refs.append(local_ref)
        for ref in refs:
            if not branch_valid(ref.removeprefix('refs/heads/'), policy):
                raise ValueError('Push rejected: invalid branch name ' + ref)


if __name__ == '__main__':
    try:
        policy = json.loads(POLICY.read_text())
        if sys.argv[1] == 'commit-msg':
            commit_message(sys.argv[2], policy)
        elif sys.argv[1] == 'pre-push':
            pre_push(sys.stdin, policy)
        else:
            raise ValueError('Unknown hook.')
    except (ValueError, OSError, subprocess.CalledProcessError) as exc:
        print(f'Git policy: {exc}', file=sys.stderr)
        sys.exit(1)
