<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Infrastructure\Serialization\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[AsController]
final readonly class StravaOAuthRequestHandler
{
    public function __construct(
        private StravaClientId $stravaClientId,
        private StravaClientSecret $stravaClientSecret,
        private Strava $strava,
        private Client $client,
        private Environment $twig,
    ) {
    }

    #[Route(path: '/strava-oauth', methods: ['GET'], priority: 2)]
    public function handle(Request $request): Response
    {
        try {
            $this->strava->getAccessToken();

            // Already authorized, load app.
            return new RedirectResponse('/', Response::HTTP_FOUND);
        } catch (ClientException|RequestException) {
        }

        if ($code = $request->query->get('code')) {
            try {
                $response = $this->client->post('https://www.strava.com/oauth/token', [
                    RequestOptions::FORM_PARAMS => [
                        'grant_type' => 'authorization_code',
                        'client_id' => (string) $this->stravaClientId,
                        'client_secret' => (string) $this->stravaClientSecret,
                        'code' => $code,
                    ],
                ]);

                $refreshToken = Json::decode($response->getBody()->getContents())['refresh_token'];

                return new Response($this->twig->render('html/strava-oauth.html.twig', [
                    'mode' => 'showRefreshToken',
                    'refreshToken' => $refreshToken,
                    'url' => $request->getSchemeAndHttpHost(),
                ]), Response::HTTP_OK);
            } catch (ClientException|RequestException $e) {
                $error = $e->getMessage();
            }
        }

        return new Response($this->twig->render('html/strava-oauth.html.twig', [
            'mode' => 'startAuthorization',
            'stravaClientId' => $this->stravaClientId,
            'returnUrl' => $request->getSchemeAndHttpHost().'/strava-oauth',
            'error' => $error ?? null,
        ]), Response::HTTP_OK);
    }
}
