<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Http\Controller;

use App\Modules\User\Application\Command\DTO\CreateUserRequest;
use App\Modules\User\Application\UseCase\CreateUser;
use App\SharedKernel\Adapters\Http\Validation\RequestValidator;
use App\SharedKernel\Adapters\Http\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Create User HTTP Controller.
 *
 * This is an INFRASTRUCTURE adapter that:
 * 1. Parses HTTP request and creates a Request DTO
 * 2. Validates the DTO using Symfony Validator
 * 3. Calls the Use Case with the validated DTO
 * 4. Returns appropriate HTTP response
 *
 * Benefits:
 * - Type-safe with DTOs
 * - Automatic validation
 * - Clear separation of concerns
 * - Easy to test
 */
final readonly class CreateUserController
{
    public function __construct(
        private CreateUser $createUser,
        private RequestValidator $validator,
    ) {}

    #[Route('/api/users', name: 'api_users_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Parse and validate request
            /** @var array<string, mixed> $data */
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $requestDTO = CreateUserRequest::fromArray($data);

            // Validate - throws ValidationException if invalid
            $this->validator->validate($requestDTO);

            $userId = Uuid::v4()->toRfc4122();

            // Execute use case with validated DTO
            $this->createUser->execute($userId, $requestDTO);

            return new JsonResponse(
                ['id' => $userId],
                Response::HTTP_CREATED,
            );
        } catch (ValidationException $e) {
            return new JsonResponse(
                ['error' => $e->getFirstError(), 'errors' => $e->getErrors()],
                Response::HTTP_BAD_REQUEST,
            );
        } catch (\DomainException|\InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        } catch (\JsonException $e) {
            return new JsonResponse(
                ['error' => 'Invalid JSON'],
                Response::HTTP_BAD_REQUEST,
            );
        }
    }
}
