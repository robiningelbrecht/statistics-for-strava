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
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[AsController]
final readonly class AppRequestHandler
{
    public function __construct(
        private FilesystemOperator $buildStorage,
        private StravaClientId $stravaClientId,
        private StravaClientSecret $stravaClientSecret,
        private Client $client,
        private Strava $strava,
        private Environment $twig,
    ) {
    }

    #[Route(path: '/{wildcard?}', requirements: ['wildcard' => '.*'], methods: ['GET'])]
    public function handle(Request $request): Response
    {
        if ($this->buildStorage->fileExists('index.html')) {
            return new Response($this->buildStorage->read('index.html'), Response::HTTP_OK);
        }

        try {
            $this->strava->getAthlete();
        } catch (ClientException|RequestException) {
            // Refresh token has not been set up properly, initialize authorization flow.
            return new Response($this->twig->render('html/strava-oauth.html.twig', [
                'mode' => 'startAuthorization',
                'stravaClientId' => $this->stravaClientId,
                'returnUrl' => $request->getSchemeAndHttpHost().'/strava-oauth',
            ]), Response::HTTP_OK);
        }

        return new Response($this->twig->render('html/setup.html.twig'), Response::HTTP_OK);
    }

    #[Route(path: '/strava-oauth', methods: ['GET'], priority: 2)]
    public function handleOauth(Request $request): Response
    {
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
                return new Response($this->twig->render('html/strava-oauth.html.twig', [
                    'mode' => 'startAuthorization',
                    'stravaClientId' => $this->stravaClientId,
                    'returnUrl' => $request->getSchemeAndHttpHost().'/strava-oauth',
                    'error' => $e->getMessage(),
                ]), Response::HTTP_OK);
            }
        }

        return new Response($this->twig->render('html/strava-oauth.html.twig', [
            'mode' => 'startAuthorization',
            'stravaClientId' => $this->stravaClientId,
            'returnUrl' => $request->getSchemeAndHttpHost().'/strava-oauth',
        ]), Response::HTTP_OK);
    }
}
