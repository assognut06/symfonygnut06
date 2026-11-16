import json
import unittest
from check_branch_policy import POLICY, check


class BranchPolicyTest(unittest.TestCase):
    def setUp(self):
        self.policy = json.loads(POLICY.read_text())
        self.pr = {
            'created_at': '2026-11-16T12:00:00Z',
            'head': {'ref': 'docs/GNUT06-153-jira-github-skills',
                     'repo': {'full_name': self.policy['repository']}},
            'title': 'docs(agents): define workflow [GNUT06-153]',
            'body': 'Ticket: https://gnut06-team.atlassian.net/browse/GNUT06-153',
        }

    def test_ticket_branch_commit_pr_example(self):
        commit = 'docs(agents): define workflow [GNUT06-153]'
        self.assertEqual(commit, self.pr['title'])
        self.assertTrue(check(self.pr, self.policy)[0])

    def test_all_branch_types(self):
        for kind in ('feature', 'fix', 'hotfix', 'refactor', 'docs', 'chore', 'test'):
            with self.subTest(kind=kind):
                self.pr['head']['ref'] = f'{kind}/GNUT06-153-valid-name'
                self.assertTrue(check(self.pr, self.policy)[0])

    def test_invalid_names(self):
        for name in ('feat/GNUT06-153-name', 'docs/gnut06-153-name',
                     'docs/GNUT06-0-name', 'docs/GNUT06-153-Name',
                     'docs/GNUT06-153-démo', 'docs/GNUT06-153-a--b',
                     'docs/GNUT06-153-name/', 'fix/tg-999-new-work',
                     'release/new', 'main-impostor'):
            with self.subTest(name=name):
                self.pr['head']['ref'] = name
                self.assertFalse(check(self.pr, self.policy)[0])

    def test_missing_or_different_reference(self):
        self.pr['body'] = None
        for title in ('No ticket', 'docs: change [GNUT06-1530]', 'XGNUT06-153'):
            self.pr['title'] = title
            self.assertFalse(check(self.pr, self.policy)[0])

    def test_body_reference_is_enough(self):
        self.pr['title'] = 'Workflow instructions'
        self.assertTrue(check(self.pr, self.policy)[0])

    def test_exact_legacy_and_permanent_exceptions(self):
        self.pr['title'] = self.pr['body'] = None
        for name in self.policy['legacy_branches'] + self.policy['permanent_branches']:
            self.pr['head']['ref'] = name
            self.assertTrue(check(self.pr, self.policy)[0])

    def test_fork_cannot_claim_exception(self):
        self.pr['head']['ref'] = 'main'
        self.pr['head']['repo'] = {'full_name': 'someone/fork'}
        self.assertFalse(check(self.pr, self.policy)[0])
        self.pr['head']['repo'] = None
        self.assertFalse(check(self.pr, self.policy)[0])

    def test_old_pr_is_preserved(self):
        self.pr['created_at'] = '2026-11-15T23:59:59Z'
        self.pr['head']['ref'] = 'old-name'
        self.pr['title'] = self.pr['body'] = None
        self.assertTrue(check(self.pr, self.policy)[0])
        self.pr['created_at'] = '2026-11-16T00:00:00Z'
        self.assertFalse(check(self.pr, self.policy)[0])


if __name__ == '__main__':
    unittest.main()
