<?php

namespace App\Tests\Functional;

use App\Service\HelloAssoApiService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test de regression contre une XSS stockee dans le tableau de bord admin.
 *
 * La description de l'organisation provient de l'API HelloAsso. Elle doit donc
 * etre consideree comme une entree externe et ne jamais etre rendue en HTML brut
 * dans une page consultee par un administrateur.
 */
class AdminDashboardXssTest extends WebTestCase
{
    /**
     * Verifie que la description externe HelloAsso est neutralisee avant rendu.
     *
     * Le test reproduit le flux complet de la page /admin : le controleur lit les
     * metadonnees de l'organisation via HelloAsso, les transmet au template Twig,
     * puis le HTML final est inspecte pour s'assurer qu'un payload <script> ne
     * devient pas executable.
     */
    public function testExternalHelloAssoDescriptionIsNotRenderedAsExecutableMarkupOnAdminDashboard(): void
    {
        // Payload volontairement concret pour detecter une utilisation dangereuse
        // de "|raw" ou toute autre desactivation de l'echappement Twig.
        $scriptPayload = '<script>alert(1337)</script>';
        $description = "Description publique HelloAsso.\n{$scriptPayload}\nFin de description.";

        // Faux service HelloAsso : il evite tout appel reseau et permet de prouver
        // que la page /admin consomme bien une description issue de l'API externe.
        $helloAssoApiService = new class($description) extends HelloAssoApiService {
            /**
             * URLs demandees par le controleur pendant le rendu de la page.
             *
             * @var list<string>
             */
            public array $requestedUrls = [];

            public function __construct(private readonly string $description)
            {
            }

            public function makeApiCall(string $url, array $headers = [], string $method = 'GET')
            {
                $this->requestedUrls[] = $url;

                return [
                    'name' => 'GNUT 06',
                    'logo' => '/images/LogoNew.png',
                    'description' => $this->description,
                    'type' => 'Association1901Rig',
                    'category' => 'Solidarite',
                    'zipCode' => '06000',
                    'city' => 'Nice',
                    'rnaNumber' => 'W000000000',
                    'url' => 'https://example.test',
                ];
            }
        };

        $client = static::createClient();
        $client->getContainer()->set(HelloAssoApiService::class, $helloAssoApiService);
        self::loginAdmin($client);

        $client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent();
        self::assertIsString($html);

        // Confirmation du flux : /admin recupere les metadonnees d'organisation
        // HelloAsso avant de rendre le template admin. Le slug peut etre vide
        // dans l'environnement de test si SLUGASSO n'est pas configure.
        $requestedUrl = $helloAssoApiService->requestedUrls[0] ?? '';
        self::assertStringStartsWith(
            'https://api.helloasso.com/v5/organizations/',
            $requestedUrl,
            'Le tableau de bord admin doit recuperer les metadonnees HelloAsso avant le rendu.'
        );

        // Le contenu texte reste affiche, mais le balisage fourni par HelloAsso ne
        // doit pas etre interprete comme du HTML executable.
        self::assertStringContainsString('Description publique HelloAsso', $html);
        self::assertStringContainsString('Fin de description', $html);
        self::assertStringNotContainsString(
            $scriptPayload,
            $html,
            sprintf(
                'La description HelloAsso externe doit etre echappee ou sanitisee avant rendu. Exemple de balisage neutralise attendu : "%s".',
                htmlspecialchars($scriptPayload, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            )
        );
        self::assertDoesNotMatchRegularExpression(
            '#<script\b[^>]*>\s*alert\(1337\)\s*</script>#i',
            $html,
            'Le tableau de bord admin ne doit pas inclure de balise script executable provenant de la description HelloAsso.'
        );
    }

    /**
     * Connecte un administrateur de test afin d'exercer la vraie route protegee /admin.
     */
    private static function loginAdmin(KernelBrowser $client): void
    {
        $userProvider = $client->getContainer()->get('security.user.provider.concrete.test_user_provider');

        $client->loginUser($userProvider->loadUserByIdentifier('admin@example.test'));
    }
}
