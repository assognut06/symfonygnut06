# Project Guidelines

Keep `AGENTS.md` and `.github/copilot-instructions.md` identical.

## Code Style
- PHP 8.2 with `strict_types` in migrations; use attributes for routing and forms.
- JavaScript: Webpack Encore for assets, Bootstrap/jQuery for UI, Stimulus for interactivity.
- Reference: `app/composer.json` for PHP dependencies, `app/package.json` for JS build scripts.

## Architecture
- Symfony 7.1 MVC framework with CQRS elements: Controllers handle requests, Services contain business logic, Repositories manage data access.
- Layers: Controllers -> Services -> Repositories/Entities; Messenger for async processing, for example notifications.
- CQRS: Commands/Queries in `src/Application/`, handled via QueryBus.
- Avoid fat controllers; refactor business logic to services.

## Build and Test
- Install: `composer install` for PHP dependencies, `npm install` for JS dependencies, `docker-compose up` for services.
- Build assets: `npm run build` for production or `npm run dev` for development.
- Database: `php bin/console doctrine:migrations:migrate` to apply migrations, `php bin/console doctrine:fixtures:load` to seed data.
- Test: `php bin/phpunit`.
- Dev server: `app/run.sh` or `start-project.sh` for full setup.

## Conventions
- Naming: PascalCase for classes, for example `HomeController` and `UserService`; camelCase for methods and properties.
- Structure: Standard Symfony folders; templates organized by feature, for example `templates/admin/`.
- Environment: Use `.env` for config; avoid hardcoded values and move them to parameters.
- Security: Never expose secrets; use environment variables for API keys and database credentials.

## Project Skills
Paths are relative to the repository root. Read the relevant skill before carrying out its workflow:
- `.agents/skills/project-jira/SKILL.md`: starting work on a Jira ticket, creating or updating tickets, changing status, or handling PR links.
- `.agents/skills/project-github/SKILL.md`: creating branches, preparing commits or pull requests, reviewing CI, or organizing delivery.

See `README.md` for installation, `AUDIT_TECHNIQUE.md` for security/architecture issues, and `DEPLOIEMENT_PRODUCTION.md` for production deployment.
