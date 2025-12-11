<?php

namespace App\Security\OAuth;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class GoogleOAuthService
{
    public function __construct(private ClientRegistry $clientRegistry)
    {
    }
    
    public function verifyIdToken(string $idToken): GoogleUser
    {
        try {

            $client = new \Google_Client(['client_id' => $_ENV['GOOGLE_CLIENT_ID']]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                throw new \Exception("Invalid ID Token");
            }

            return new GoogleUser($payload);

        } catch (IdentityProviderException $e) {
            throw new \RuntimeException('Invalid Google token: ' . $e->getMessage());
        }
    }
}
