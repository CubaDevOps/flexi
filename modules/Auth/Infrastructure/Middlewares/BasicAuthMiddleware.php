<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Contracts\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\SessionStorageInterface;
use CubaDevOps\Flexi\Domain\Events\Event;
use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsRepositoryInterface;
use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsVerifierInterface;
use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\AuthenticationResult;
use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\Credentials;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * BasicAuthMiddleware - HTTP Basic Authentication Middleware
 *
 * Handles HTTP Basic Authentication via Authorization header.
 * Credentials format: Authorization: Basic base64(username:password)
 *
 * Security features:
 * - Extracts credentials from Authorization header
 * - Validates credentials against repository
 * - Uses secure password verification (prevents timing attacks)
 * - Creates secure session upon success
 * - Logs all authentication attempts
 * - Dispatches events for audit/monitoring
 * - Returns 401 Unauthorized on failure
 *
 * Integration:
 * - Uses CredentialsRepositoryInterface for credential storage (agnostic)
 * - Uses CredentialsVerifierInterface for password verification (agnostic)
 * - Uses SessionStorageInterface to store authenticated session
 * - Uses EventBusInterface to dispatch authentication events
 * - Uses LoggerInterface for audit logging
 */
final class BasicAuthMiddleware implements MiddlewareInterface
{
    private CredentialsRepositoryInterface $credentials_repository;
    private CredentialsVerifierInterface $credentials_verifier;
    private SessionStorageInterface $session;
    private ResponseFactoryInterface $response_factory;
    private EventBusInterface $event_bus;
    private LoggerInterface $logger;

    public function __construct(
        CredentialsRepositoryInterface $credentials_repository,
        CredentialsVerifierInterface $credentials_verifier,
        SessionStorageInterface $session,
        ResponseFactoryInterface $response_factory,
        EventBusInterface $event_bus,
        LoggerInterface $logger
    ) {
        $this->credentials_repository = $credentials_repository;
        $this->credentials_verifier = $credentials_verifier;
        $this->session = $session;
        $this->response_factory = $response_factory;
        $this->event_bus = $event_bus;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth_header = $request->getHeaderLine('Authorization');

        // Step 1: Extract credentials from Authorization header
        $credentials = $this->extractCredentials($auth_header, $request);

        if (null === $credentials) {
            return $this->handleAuthenticationFailure(
                'missing_credentials',
                'Missing or invalid Authorization header',
                $request
            );
        }

        // Step 2: Find user in repository
        $user_credentials = $this->credentials_repository->findByUsername($credentials->getUsername());

        if (null === $user_credentials) {
            return $this->handleAuthenticationFailure(
                'user_not_found',
                sprintf('User not found: %s', $credentials->getUsername()),
                $request
            );
        }

        // Step 3: Verify password
        if (!$this->credentials_verifier->verify($credentials, $user_credentials['password_hash'])) {
            return $this->handleAuthenticationFailure(
                'invalid_password',
                sprintf('Invalid password for user: %s', $credentials->getUsername()),
                $request
            );
        }

        // Step 4: Authentication successful - create session
        $authentication_result = new AuthenticationResult(
            $user_credentials['user_id'],
            $user_credentials['username'],
            array_diff_key($user_credentials, array_flip(['username', 'password_hash', 'user_id']))
        );

        $this->createAuthenticatedSession($authentication_result);

        // Step 5: Log successful authentication
        $this->logger->info('User authenticated successfully', [
            'username' => $credentials->getUsername(),
            'user_id' => $authentication_result->getUserId(),
            'ip' => $this->getClientIp($request),
        ]);

        // Step 6: Dispatch authentication success event
        $this->event_bus->dispatch(new Event(
            'user.authenticated',
            self::class,
            [
                'user_id' => $authentication_result->getUserId(),
                'username' => $authentication_result->getUsername(),
                'ip' => $this->getClientIp($request),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]
        ));

        // Step 7: Pass to next handler
        return $handler->handle($request);
    }

    /**
     * Extract and decode credentials from Authorization header
     *
     * @param string                 $auth_header Authorization header value
     * @param ServerRequestInterface $request     HTTP request for context
     *
     * @return Credentials|null Extracted credentials or null if invalid
     */
    private function extractCredentials(string $auth_header, ServerRequestInterface $request): ?Credentials
    {
        // Check if Authorization header starts with "Basic "
        if (0 !== stripos($auth_header, 'Basic ')) {
            return null;
        }

        // Extract base64 encoded credentials
        $encoded_credentials = substr($auth_header, 6);

        if (empty($encoded_credentials)) {
            return null;
        }

        // Decode base64
        $decoded = base64_decode($encoded_credentials, true);

        if (false === $decoded) {
            $this->logger->warning('Failed to decode Authorization header', [
                'ip' => $this->getClientIp($request),
            ]);

            return null;
        }

        // Split username:password
        $parts = explode(':', $decoded, 2);

        if (2 !== \count($parts)) {
            $this->logger->warning('Invalid Authorization header format', [
                'ip' => $this->getClientIp($request),
            ]);

            return null;
        }

        [$username, $password] = $parts;

        try {
            return new Credentials($username, $password);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid credentials format', [
                'reason' => $e->getMessage(),
                'ip' => $this->getClientIp($request),
            ]);

            return null;
        }
    }

    /**
     * Store authenticated session in session storage
     *
     * @param AuthenticationResult $result Authentication result
     *
     * @return void
     */
    private function createAuthenticatedSession(AuthenticationResult $result): void
    {
        $session_data = $result->toSessionData();

        foreach ($session_data as $key => $value) {
            $this->session->set($key, $value);
        }

        // Also set a flag indicating user is authenticated
        $this->session->set('auth', true);
    }

    /**
     * Handle authentication failure
     *
     * @param string                 $failure_reason Reason for failure (user_not_found, invalid_password, etc.)
     * @param string                 $log_message    Message to log
     * @param ServerRequestInterface $request        HTTP request for context
     *
     * @return ResponseInterface 401 Unauthorized response
     */
    private function handleAuthenticationFailure(
        string $failure_reason,
        string $log_message,
        ServerRequestInterface $request
    ): ResponseInterface {
        // Log authentication failure
        $this->logger->warning($log_message, [
            'reason' => $failure_reason,
            'ip' => $this->getClientIp($request),
        ]);

        // Dispatch authentication failed event
        $this->event_bus->dispatch(new Event(
            'user.authentication.failed',
            self::class,
            [
                'reason' => $failure_reason,
                'ip' => $this->getClientIp($request),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]
        ));

        // Return 401 response
        $response = $this->response_factory->createResponse(401);
        $response->getBody()->write('Unauthorized');

        return $response;
    }

    /**
     * Get client IP address from request
     *
     * @param ServerRequestInterface $request HTTP request
     *
     * @return string Client IP address
     */
    private function getClientIp(ServerRequestInterface $request): string
    {
        $server_params = $request->getServerParams();

        // Check for X-Forwarded-For header (proxied requests)
        $forwarded_for = $request->getHeaderLine('X-Forwarded-For');
        if (!empty($forwarded_for)) {
            // Take first IP if multiple are provided
            $ips = explode(',', $forwarded_for);

            return trim($ips[0]);
        }

        // Check for X-Real-IP header
        $real_ip = $request->getHeaderLine('X-Real-IP');
        if (!empty($real_ip)) {
            return $real_ip;
        }

        // Fall back to REMOTE_ADDR
        return $server_params['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
