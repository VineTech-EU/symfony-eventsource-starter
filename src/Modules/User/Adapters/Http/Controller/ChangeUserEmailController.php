<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Http\Controller;

use App\Modules\User\Application\Command\DTO\ChangeUserEmailRequest;
use App\Modules\User\Application\UseCase\ChangeUserEmail;
use App\SharedKernel\Adapters\Http\Validation\RequestValidator;
use App\SharedKernel\Adapters\Http\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Change User Email HTTP Controller.
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
final readonly class ChangeUserEmailController
{
    public function __construct(
        private ChangeUserEmail $changeUserEmail,
        private RequestValidator $validator,
    ) {}

    #[Route('/api/users/{id}/email', name: 'api_users_change_email', methods: ['PATCH'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        try {
            // Parse and validate request
            /** @var array<string, mixed> $data */
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $requestDTO = ChangeUserEmailRequest::fromArray($id, $data);

            // Validate - throws ValidationException if invalid
            $this->validator->validate($requestDTO);

            // Execute use case with validated DTO
            $this->changeUserEmail->execute($requestDTO);

            return new JsonResponse(
                ['message' => 'Email changed successfully'],
                Response::HTTP_OK,
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
