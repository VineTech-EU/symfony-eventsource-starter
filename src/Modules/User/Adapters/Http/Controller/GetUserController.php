<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Http\Controller;

use App\Modules\User\Application\UseCase\GetUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Get User HTTP Controller.
 *
 * This controller demonstrates calling a Use Case DIRECTLY.
 *
 * For queries, direct call is often preferred because:
 * - Queries are synchronous by nature
 * - Need immediate result
 * - No benefit from async processing
 */
final readonly class GetUserController
{
    public function __construct(
        private GetUser $getUser,
    ) {}

    #[Route('/api/users/{id}', name: 'api_users_get', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        // Call Use Case directly
        $user = $this->getUser->execute(userId: $id);

        // HTTP-specific: Handle not found
        if (null === $user) {
            return new JsonResponse(
                ['error' => 'User not found'],
                Response::HTTP_NOT_FOUND,
            );
        }

        // HTTP-specific: Format response
        return new JsonResponse($user->toArray());
    }
}
