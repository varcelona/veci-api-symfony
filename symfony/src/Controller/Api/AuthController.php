<?php
namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AuthController
{
    #[Route('/auth/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Nunca se ejecuta realmente:
        throw new \LogicException('Este método está gestionado por el firewall json_login.');
    }
}
