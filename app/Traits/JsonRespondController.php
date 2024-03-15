<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;

trait JsonRespondController
{
    protected int $httpStatusCode = 200;

    protected ?int $errorCode = null;

    public function getHTTPStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function setHTTPStatusCode(int $statusCode): self
    {
        $this->httpStatusCode = $statusCode;

        return $this;
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    public function setErrorCode(int $errorCode): self
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function respond(array $data, array $headers = []): JsonResponse
    {
        return response()->json($data, $this->getHTTPStatusCode(), $headers);
    }

    /**
     * Sends a response not found (404) to the request.
     * Error Code = 31.
     */
    public function respondNotFound(): JsonResponse
    {
        return $this->setHTTPStatusCode(404)
            ->setErrorCode(31)
            ->respondWithError();
    }

    /**
     * Sends an error when the validator failed.
     * Error Code = 32.
     */
    public function respondValidatorFailed(Validator $validator): JsonResponse
    {
        return $this->setHTTPStatusCode(422)
            ->setErrorCode(32)
            ->respondWithError($validator->errors()->all());
    }

    /**
     * Sends an error when the query didn't have the right parameters for
     * creating an object.
     * Error Code = 33.
     */
    public function respondNotTheRightParameters(?string $message = null): JsonResponse
    {
        return $this->setHTTPStatusCode(500)
            ->setErrorCode(33)
            ->respondWithError($message);
    }

    /**
     * Sends a response invalid query (http 500) to the request.
     * Error Code = 40.
     */
    public function respondInvalidQuery(?string $message = null): JsonResponse
    {
        return $this->setHTTPStatusCode(500)
            ->setErrorCode(40)
            ->respondWithError($message);
    }

    /**
     * Sends an error when the query contains invalid parameters.
     * Error Code = 41.
     */
    public function respondInvalidParameters(?string $message = null): JsonResponse
    {
        return $this->setHTTPStatusCode(422)
            ->setErrorCode(41)
            ->respondWithError($message);
    }

    /**
     * Sends a response unauthorized (401) to the request.
     * Error Code = 42.
     */
    public function respondUnauthorized(?string $message = null): JsonResponse
    {
        return $this->setHTTPStatusCode(401)
            ->setErrorCode(42)
            ->respondWithError($message);
    }

    /**
     * Sends a response with error.
     */
    public function respondWithError(array|string|null $message = null): JsonResponse
    {
        return $this->respond([
            'error' => [
                'message' => $message ?? config('api.error_codes.'.$this->getErrorCode()),
                'error_code' => $this->getErrorCode(),
            ],
        ]);
    }

    /**
     * Sends a response that the object has been deleted, and also indicates
     * the id of the object that has been deleted.
     */
    public function respondObjectDeleted(string $id): JsonResponse
    {
        return $this->respond([
            'deleted' => true,
            'id' => $id,
        ]);
    }
}
