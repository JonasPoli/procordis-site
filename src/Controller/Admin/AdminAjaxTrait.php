<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Utilitários para as ações AJAX do painel (alternar status, galeria etc.).
 *
 * O token CSRF é gerado no template com csrf_token('admin_ajax') e enviado no cabeçalho X-CSRF-Token.
 *
 * @mixin \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
 */
trait AdminAjaxTrait
{
    public const AJAX_CSRF_TOKEN_ID = 'admin_ajax';

    /**
     * Retorna uma resposta de erro se o token CSRF for inválido, ou null se estiver tudo certo.
     */
    private function checkAjaxCsrf(Request $request): ?JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token') ?? $request->request->getString('_token');

        if (!$this->isCsrfTokenValid(self::AJAX_CSRF_TOKEN_ID, $token)) {
            return $this->jsonError('Sua sessão expirou ou o formulário ficou desatualizado. Recarregue a página e tente novamente.', Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    private function jsonError(string $message, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }

    /**
     * Lê o corpo JSON da requisição (ou um array vazio se o corpo for inválido).
     *
     * @return array<string, mixed>
     */
    private function jsonBody(Request $request): array
    {
        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            return [];
        }

        return $data;
    }
}
