<?php

namespace App\Tests\Functional;

use App\Entity\Competence;
use App\Entity\Tih;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class WebTestCase extends BaseWebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;
    private bool $clientSessionStarted = false;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Starts an HTTP-backed test session (required by CsrfTokenManager).
     * loginUser() alone does not trigger a request, so tokens must be created
     * after at least one client request in the same cookie jar.
     */
    protected function bootstrapClientSession(string $path = '/'): void
    {
        if ($this->clientSessionStarted) {
            return;
        }

        $this->client->request('GET', $path);
        $this->clientSessionStarted = true;
    }

    /**
     * @deprecated use bootstrapClientSession()
     */
    protected function ensureClientSession(): void
    {
        $this->bootstrapClientSession('/');
    }

    /**
     * Generates a CSRF token in the browser test session.
     * The token id must match the controller (e.g. validate_tih42, delete17).
     */
    protected function generateCsrfToken(string $tokenId): string
    {
        $this->bootstrapClientSession('/');

        return $this->readOrCreateCsrfToken($tokenId);
    }

    /**
     * Reads an existing CSRF token from the current session or creates one.
     * Call bootstrapClientSession() with the form page path first for Symfony forms.
     */
    protected function readOrCreateCsrfToken(string $tokenId): string
    {
        $container = $this->client->getContainer();
        $request = $this->client->getRequest();
        $session = $request->getSession();
        $requestStack = $container->get('request_stack');
        $requestStack->push($request);

        try {
            $token = $container->get('security.csrf.token_manager')
                ->getToken($tokenId)
                ->getValue();
            $session->save();

            return $token;
        } finally {
            $requestStack->pop();
        }
    }

    protected function createUser(
        string $email = 'user@test.com',
        string $password = 'Test1234!',
        array $roles = [],
        bool $verified = true
    ): User {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setRoles($roles);
        $user->setVerified($verified);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createAdmin(
        string $email = 'admin@test.com',
        string $password = 'Admin1234!'
    ): User {
        return $this->createUser($email, $password, ['ROLE_ADMIN']);
    }

    protected function createTihUser(
        string $email = 'tih@test.com',
        string $password = 'Tih1234!'
    ): User {
        $this->createSearchableTih([
            'email' => $email,
            'password' => $password,
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'city' => 'Nice',
            'region' => 'PACA',
            'validated' => true,
        ]);

        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    /**
     * @param array{
     *     email?: string,
     *     password?: string,
     *     firstName?: string,
     *     lastName?: string,
     *     city?: string,
     *     region?: string,
     *     departement?: string,
     *     rate?: string,
     *     rateType?: string,
     *     validated?: bool,
     *     competences?: Competence[],
     * } $options
     */
    protected function createSearchableTih(array $options = []): Tih
    {
        $email = $options['email'] ?? sprintf('tih-%s@test.com', uniqid());

        $user = $this->createUser(
            $email,
            $options['password'] ?? 'Tih1234!',
            ['ROLE_TIH'],
        );

        $tih = new Tih();
        $tih->setUser($user);
        $tih->setFirstName($options['firstName'] ?? 'Jean');
        $tih->setLastName($options['lastName'] ?? 'Dupont');
        $tih->setCity($options['city'] ?? 'Nice');
        $tih->setRegion($options['region'] ?? 'PACA');
        $tih->setDepartement($options['departement'] ?? null);
        $tih->setRate($options['rate'] ?? null);
        $tih->setRateType($options['rateType'] ?? null);
        $tih->setIsValidate($options['validated'] ?? true);

        foreach ($options['competences'] ?? [] as $competence) {
            $tih->addCompetence($competence);
        }

        $user->setTih($tih);

        $this->em->persist($tih);
        $this->em->flush();

        return $tih;
    }

    protected function createCompetence(string $name): Competence
    {
        $competence = new Competence();
        $competence->setName($name);
        $this->em->persist($competence);
        $this->em->flush();

        return $competence;
    }

    protected function assertTihGridContains(string $text): void
    {
        $this->assertSelectorTextContains('#tih-grid', $text);
    }

    protected function assertTihGridNotContains(string $text): void
    {
        $this->assertSelectorTextNotContains('#tih-grid', $text);
    }

    protected function assertTihResultCount(int $expected): void
    {
        $this->assertCount(
            $expected,
            $this->client->getCrawler()->filter('#tih-grid article[role="listitem"]'),
            sprintf('Expected %d TIH profile(s) in search results.', $expected)
        );
    }

    protected function loginAs(User $user): void
    {
        $this->client->loginUser($user);
    }

    protected function loginAsNewUser(): User
    {
        $user = $this->createUser();
        $this->loginAs($user);
        return $user;
    }

    protected function loginAsAdmin(): User
    {
        $admin = $this->createAdmin();
        $this->loginAs($admin);
        return $admin;
    }

    protected function assertResponseRedirectsToLogin(): void
    {
        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('login', $location);
    }

    protected function getAdminTihCsrfToken(string $action, int $tihId): string
    {
        return match ($action) {
            'validate' => $this->generateCsrfToken('validate_tih' . $tihId),
            'refuse' => $this->generateCsrfToken('refuse_tih' . $tihId),
            'delete' => $this->generateCsrfToken('delete_tih' . $tihId),
            default => throw new \InvalidArgumentException(sprintf('Unknown TIH admin action "%s".', $action)),
        };
    }

    protected function getAdminUserPromoteCsrfToken(int $userId): string
    {
        return $this->generateCsrfToken('promote_user' . $userId);
    }

    protected function getAdminUserDeleteCsrfToken(int $userId): string
    {
        return $this->generateCsrfToken('delete' . $userId);
    }

    protected function submitLogin(string $email, string $password): void
    {
        $this->bootstrapClientSession('/login');

        $this->client->request('POST', '/login', [
            '_username' => $email,
            '_password' => $password,
            '_csrf_token' => $this->readOrCreateCsrfToken('authenticate'),
        ]);
    }

    /**
     * @param array{
     *     email: string,
     *     plainPassword: string,
     *     confirmPassword: string,
     *     agreeTerms: bool,
     *     isTih?: bool,
     * } $data
     */
    protected function submitRegistrationForm(array $data): void
    {
        $this->bootstrapClientSession('/register');

        $payload = [
            'registration_form' => [
                'email' => $data['email'],
                'plainPassword' => $data['plainPassword'],
                'confirmPassword' => $data['confirmPassword'],
                '_token' => $this->readOrCreateCsrfToken('registration_form'),
            ],
            'g-recaptcha-response' => '',
        ];

        if ($data['agreeTerms']) {
            $payload['registration_form']['agreeTerms'] = '1';
        }

        if (!empty($data['isTih'])) {
            $payload['registration_form']['isTih'] = '1';
        }

        $this->client->request('POST', '/register', $payload);
    }

    protected function submitResetPasswordRequest(string $email): void
    {
        $this->bootstrapClientSession('/reset-password');

        $this->client->request('POST', '/reset-password', [
            'reset_password_request_form' => [
                'email' => $email,
                '_token' => $this->readOrCreateCsrfToken('reset_password_request_form'),
            ],
            'g-recaptcha-response' => '',
        ]);
    }

    /**
     * @param array<string, string> $data
     */
    protected function submitContactForm(array $data): void
    {
        $this->bootstrapClientSession('/contact');

        $this->client->request('POST', '/contact', array_merge(
            [
                '_token' => $this->readOrCreateCsrfToken('App\\Form\\ContactType'),
                'g-recaptcha-response' => '',
            ],
            $data
        ));
    }

    protected function submitDeleteProfilePicture(int $userId): void
    {
        $this->client->request('POST', '/profile/delete-picture', [
            '_token' => $this->generateCsrfToken('delete_profile_picture' . $userId),
        ]);
    }

    protected function assertResponseHtmlContainsForm(int $minimum = 1): void
    {
        $html = $this->client->getResponse()->getContent();
        $this->assertGreaterThanOrEqual(
            $minimum,
            preg_match_all('/<form\b/i', $html),
            sprintf('Expected at least %d <form> in response HTML.', $minimum)
        );
    }
}
