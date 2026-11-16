import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class LocalHooksTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.repo = Path(self.tmp.name) / 'repo'
        self.repo.mkdir()
        self.env = {k: v for k, v in os.environ.items() if not k.startswith('GIT_')}
        self.env.update(GIT_CONFIG_NOSYSTEM='1', GIT_CONFIG_GLOBAL=os.devnull)
        self.run_cmd('git', 'init', '-b', 'docs/GNUT06-153-hooks')
        self.run_cmd('git', 'config', 'user.name', 'Hook Test')
        self.run_cmd('git', 'config', 'user.email', 'hook@example.invalid')
        shutil.copytree(ROOT / '.githooks', self.repo / '.githooks')
        shutil.copytree(ROOT / '.github', self.repo / '.github')
        self.run_cmd('python3', '.github/scripts/install_git_hooks.py')

    def run_cmd(self, *args, ok=True, input=None):
        result = subprocess.run(args, cwd=self.repo, env=self.env, text=True,
                                input=input, capture_output=True)
        if ok:
            self.assertEqual(result.returncode, 0, result.stderr)
        else:
            self.assertNotEqual(result.returncode, 0, result.stdout)
        return result

    def commit(self, message, ok=True):
        return self.run_cmd('git', 'commit', '--allow-empty', '-m', message, ok=ok)

    def test_real_commits_and_branch_checks(self):
        self.commit('docs: install hooks [GNUT06-153]')
        self.commit('invalid message', ok=False)
        self.commit('docs: wrong ticket [GNUT06-154]', ok=False)
        self.commit('feat: missing description', ok=False)
        self.run_cmd('git', 'switch', '-c', 'bad-new-branch')
        self.commit('docs: valid message [GNUT06-153]', ok=False)
        self.run_cmd('git', 'switch', '-c', 'fix/tg-154-securite-npm')
        self.commit('Legacy branch remains unchanged')
        self.run_cmd('git', 'switch', '-c', 'main')
        self.commit('bad permanent branch commit', ok=False)
        self.commit('chore: valid permanent branch commit [GNUT06-153]')

    def test_real_push_and_explicit_destination(self):
        self.commit('docs: hooks [GNUT06-153]')
        remote = str(Path(self.tmp.name) / 'remote.git')
        self.run_cmd('git', 'init', '--bare', remote)
        self.run_cmd('git', 'push', remote, 'HEAD:refs/heads/docs/GNUT06-153-hooks')
        self.run_cmd('git', 'push', remote, 'HEAD:refs/heads/bad-name', ok=False)
        self.run_cmd('git', 'tag', 'test-tag')
        self.run_cmd('git', 'push', remote, 'refs/tags/test-tag')
        self.run_cmd('git', 'push', remote, ':refs/heads/docs/GNUT06-153-hooks')

    def test_installer_preserves_existing_configuration(self):
        self.run_cmd('python3', '.github/scripts/install_git_hooks.py')
        self.run_cmd('git', 'config', '--local', 'core.hooksPath', 'custom-hooks')
        self.run_cmd('python3', '.github/scripts/install_git_hooks.py', ok=False)
        result = self.run_cmd('git', 'config', '--get', 'core.hooksPath')
        self.assertEqual(result.stdout.strip(), 'custom-hooks')
        self.run_cmd('git', 'config', '--unset', 'core.hooksPath')
        hook = self.repo / '.git/hooks/pre-commit'
        hook.write_text('#!/bin/sh\nexit 0\n')
        hook.chmod(0o755)
        self.run_cmd('python3', '.github/scripts/install_git_hooks.py', ok=False)

    def test_real_merge_is_exempt(self):
        self.commit('docs: initial [GNUT06-153]')
        self.run_cmd('git', 'switch', '-c', 'docs/GNUT06-153-other')
        self.commit('docs: side [GNUT06-153]')
        self.run_cmd('git', 'switch', 'docs/GNUT06-153-hooks')
        self.commit('docs: main side [GNUT06-153]')
        self.run_cmd('git', 'merge', '--no-ff', 'docs/GNUT06-153-other', '-m', 'Merge side branch')
        self.commit('Merge fake exemption', ok=False)
