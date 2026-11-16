---
name: project-jira
description: Read and manage GNUT06 Jira tickets and their GitHub links when starting ticket work or handling ticket creation, updates, status transitions or PR references.
---

# Project Jira workflow

Site: https://gnut06-team.atlassian.net. Project: `GNUT06`.
Use [project-github](../project-github/SKILL.md) for branch, commit, PR and delivery conventions.

## Start ticket work

1. Reuse the configured Atlassian integration or an already authenticated API. Resolve the site once per session. Never display tokens or request that they be committed.
2. Read the ticket, description, acceptance criteria, comments and relevant links. Paginate comments when needed. Distinguish requirements, proposals and explicit user decisions.
3. Inspect repository state and linked PRs before duplicating existing work. Jira development counters can be stale; verify them on GitHub.
4. Reuse the ticket key, requirements and work type to derive the branch name with the GitHub skill. Ask only for information genuinely missing from the available context.

If the key is missing, search for the ticket using the available context. If several candidates remain plausible, ask which one to use. If the ticket does not exist, do not invent a number or create a branch with an unverified key. Prepare a new ticket if that falls within the request; create it only when authorized.

If access is unavailable, state the limitation precisely. Continue independent local analysis, but do not claim to have verified the ticket or create its branch before verification.

## Writes and progress tracking

- Reuse authorization already provided in the conversation. A coding request does not grant general permission to edit Jira. Reading a ticket does not authorize comments, notifications or status changes.
- Before an authorized creation, search for duplicates. Include a concrete title, problem, scope and acceptance criteria. Resolve the project's available issue types and fields; do not invent user or status identifiers.
- If a creation or update returns an uncertain outcome, read or search for the object before retrying. If uncertainty remains, stop writes and explain it rather than creating a duplicate ticket.
- Before an authorized update, reread the content and preserve unrelated material. For a transition, inspect the transitions actually available. Do not mark work done merely because coding has ended: consider acceptance criteria, validation and the agreed delivery process.
- Keep the same Jira key in the branch, commits and PR, and include the ticket URL in the PR body. Add the actual PR link to the ticket only when that write is authorized, avoiding duplicate links. Do not change global Jira settings.

## Example without remote writes

For `GNUT06-153`, read the existing ticket and prepare:

- Branch: `docs/GNUT06-153-jira-github-skills`.
- Commit: `docs(agents): define Jira and GitHub workflow [GNUT06-153]`.
- PR targeting `develop`, using the same title and linking to https://gnut06-team.atlassian.net/browse/GNUT06-153. Describe the skills, conventions, tests and limitations.

The simulation checks these references and the delivery path without creating tickets, commits or PRs. A textual reference alone does not prove that Jira has indexed a PR; verify the actual link after authorized publication.
