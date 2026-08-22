<?php

namespace App\Controller\Trait;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait JsonCsrfTokenTrait
{
    /**
     * Extrait le jeton CSRF du corps JSON d'une requête AJAX.
     * Retourne une 400 propre si le corps est absent, non-JSON ou ne contient pas de _token,
     * plutôt qu'un TypeError fatal sur un accès de tableau null.
     */
    private function getCsrfTokenFromJson(Request $request): string
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['_token']) || !is_string($data['_token'])) {
            throw new BadRequestHttpException('Corps de requête JSON invalide : "_token" attendu.');
        }

        return $data['_token'];
    }
}
