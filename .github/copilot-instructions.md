# Project Guidelines

## Code Style
- PHP 8.2 with `strict_types` in migrations; use attributes for routing and forms.
- JavaScript: Webpack Encore for assets, Bootstrap/jQuery for UI, Stimulus for interactivity.
- Reference: `app/composer.json` for PHP dependencies, `app/package.json` for JS build scripts.

## Architecture
- Symfony 7.1 MVC framework with CQRS elements: Controllers handle requests, Services contain business logic, Repositories manage data access.
- Layers: Controllers → Services → Repositories/Entities; Messenger for async processing (e.g., notifications).
- CQRS: Commands/Queries in `src/Application/`, handled via QueryBus.
- Avoid fat controllers; refactor business logic to services.

## Build and Test
- Install: `composer install` (PHP deps), `npm install` (JS deps), `docker-compose up` (services).
- Build assets: `npm run build` (production) or `npm run dev` (development).
- Database: `php bin/console doctrine:migrations:migrate` (apply migrations), `php bin/console doctrine:fixtures:load` (seed data).
- Test: `php bin/phpunit` (though currently no tests exist).
- Dev server: `app/run.sh` or `start-project.sh` for full setup.

## Conventions
- Naming: PascalCase for classes (e.g., `HomeController`, `UserService`), camelCase for methods/properties.
- Structure: Standard Symfony folders; templates organized by feature (e.g., `templates/admin/`).
- Environment: Use `.env` for config; avoid hardcoded values—move to parameters.
- Security: Never expose secrets; use environment variables for API keys, DB credentials.

See README.md for installation, AUDIT_TECHNIQUE.md for security/architecture issues, DEPLOIEMENT_PRODUCTION.md for production deployment.