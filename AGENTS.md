# Project Guidelines

This file mirrors `.github/copilot-instructions.md` for coding agents such as Codex.

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

## Branch Naming and Deployment Flow
- Branch names must follow `<type>/tg-<taiga-ticket-number>-<free-text>`.
- Allowed branch types are `feature`, `fix`, and `hotfix`.
- `feature/*` and `fix/*` branches follow the standard delivery flow: merge into `develop` first, then into `main`.
- `hotfix/*` branches follow the emergency production flow: merge into `main` first, then backport or merge into `develop`.
- Keep the free-text part short and descriptive so the branch remains easy to identify.

See `README.md` for installation, `AUDIT_TECHNIQUE.md` for security/architecture issues, and `DEPLOIEMENT_PRODUCTION.md` for production deployment.
