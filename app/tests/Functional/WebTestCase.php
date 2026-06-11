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

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
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

    protected function extractCsrfTokenFromHtml(string $html, array $patterns): string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $matches[1];
            }
        }

        throw new \RuntimeException('Could not extract CSRF token from rendered admin page.');
    }

    protected function extractCsrfTokenFromPage(string $selector, string $html, array $htmlPatterns): string
    {
        $nodes = $this->client->getCrawler()->filter($selector);
        if ($nodes->count() > 0) {
            return $nodes->attr('value');
        }

        return $this->extractCsrfTokenFromHtml($html, $htmlPatterns);
    }

    protected function getAdminTihCsrfToken(string $action, int $tihId): string
    {
        $query = [];
        $tih = $this->em->getRepository(Tih::class)->find($tihId);
        $email = $tih?->getUser()?->getEmail();
        if ($email !== null && $email !== '') {
            $query['q'] = $email;
        }

        $this->client->request('GET', '/admin/tih', $query);
        $html = $this->client->getResponse()->getContent();

        return match ($action) {
            'validate' => $this->extractCsrfTokenFromPage(
                sprintf('form[action$="/admin/tih/validate/%d"] input[name="_token"]', $tihId),
                $html,
                [
                    sprintf('#/admin/tih/validate/%1$d.*?name="_token"\s+value="([^"]+)"#s', $tihId),
                    sprintf('#/admin/tih/validate/%1$d.*?value="([^"]+)"\s+name="_token"#s', $tihId),
                ]
            ),
            'refuse' => $this->extractCsrfTokenFromPage(
                sprintf('#refuse-form-%d input[name="_token"]', $tihId),
                $html,
                [
                    sprintf('#refuse-form-%1$d.*?name="_token"\s+value="([^"]+)"#s', $tihId),
                    sprintf('#refuse-form-%1$d.*?value="([^"]+)"\s+name="_token"#s', $tihId),
                ]
            ),
            'delete' => $this->extractCsrfTokenFromPage(
                sprintf('form[action$="/admin/tih/delete/%d"] input[name="_token"]', $tihId),
                $html,
                [
                    sprintf('#/admin/tih/delete/%1$d.*?name="_token"\s+value="([^"]+)"#s', $tihId),
                    sprintf('#/admin/tih/delete/%1$d.*?value="([^"]+)"\s+name="_token"#s', $tihId),
                ]
            ),
            default => throw new \InvalidArgumentException(sprintf('Unknown TIH admin action "%s".', $action)),
        };
    }

    protected function getAdminUserPromoteCsrfToken(int $userId): string
    {
        $query = [];
        $user = $this->em->getRepository(User::class)->find($userId);
        if ($user?->getEmail()) {
            $query['q'] = $user->getEmail();
        }

        $this->client->request('GET', '/admin/user', $query);
        $html = $this->client->getResponse()->getContent();

        return $this->extractCsrfTokenFromPage(
            sprintf('form[action$="/admin/user/promote/%d"] input[name="_token"]', $userId),
            $html,
            [
                sprintf('#/admin/user/promote/%1$d.*?name="_token"\s+value="([^"]+)"#s', $userId),
                sprintf('#/admin/user/promote/%1$d.*?value="([^"]+)"\s+name="_token"#s', $userId),
            ]
        );
    }

    protected function getAdminUserDeleteCsrfToken(int $userId): string
    {
        $query = [];
        $user = $this->em->getRepository(User::class)->find($userId);
        if ($user?->getEmail()) {
            $query['q'] = $user->getEmail();
        }

        $this->client->request('GET', '/admin/user', $query);
        $html = $this->client->getResponse()->getContent();

        return $this->extractCsrfTokenFromPage(
            sprintf('form[action$="/admin/user/delete/%d"] input[name="_token"]', $userId),
            $html,
            [
                sprintf('#/admin/user/delete/%1$d.*?name="_token"\s+value="([^"]+)"#s', $userId),
                sprintf('#/admin/user/delete/%1$d.*?value="([^"]+)"\s+name="_token"#s', $userId),
            ]
        );
    }
}
