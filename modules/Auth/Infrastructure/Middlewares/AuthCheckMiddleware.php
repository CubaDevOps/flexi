<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Contracts\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\SessionStorageInterface;
use CubaDevOps\Flexi\Domain\Events\Event;
use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\AuthorizedUser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * AuthCheckMiddleware - Validates user session authentication
 *
 * This middleware verifies that:
 * 1. User has an active authenticated session
 * 2. Session contains required user data (user_id, username)
 * 3. Session has not expired (optional, configurable)
 * 4. All session data is valid
 *
 * If authentication is valid:
 * - Creates AuthorizedUser value object
 * - Attaches it to request as 'authorized_user' attribute
 * - Dispatches 'user.session.validated' event
 * - Passes to next middleware
 *
 * If authentication fails:
 * - Logs the failure
 * - Dispatches 'user.session.invalid' event
 * - Returns 401 Unauthorized
 *
 * Configuration:
 * - session_timeout: Session expiration time in seconds (0 = no expiration)
 *   Can be set via:
 *   - Constructor parameter
 *   - ENV variable: AUTH_SESSION_TIMEOUT
 *   - Default: 3600 (1 hour)
 */
final class AuthCheckMiddleware implements MiddlewareInterface
{
    private SessionStorageInterface $session;
    private ResponseFactoryInterface $response_factory;
    private EventBusInterface $event_bus;
    private LoggerInterface $logger;
    private int $session_timeout;

    public function __construct(
        SessionStorageInterface $session,
        ResponseFactoryInterface $response_factory,
        EventBusInterface $event_bus,
        LoggerInterface $logger,
        int $session_timeout = 3600
    ) {
        $this->session = $session;
        $this->response_factory = $response_factory;
        $this->event_bus = $event_bus;
        $this->logger = $logger;
        // Allow override via environment variable
        $this->session_timeout = (int) ($_ENV['AUTH_SESSION_TIMEOUT'] ?? $session_timeout);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Step 1: Check if session indicates user is authenticated
        if (!$this->session->has('auth') || true !== $this->session->get('auth')) {
            return $this->handleAuthenticationFailure(
                $request,
                'no_active_session',
                'No active authenticated session'
            );
        }

        // Step 2: Validate required session data
        if (!$this->session->has('user_id') || !$this->session->has('username')) {
            $this->logger->error('Session missing required user data', [
                'has_user_id' => $this->session->has('user_id'),
                'has_username' => $this->session->has('username'),
            ]);

            return $this->handleAuthenticationFailure(
                $request,
                'incomplete_session',
                'Session is incomplete or corrupted'
            );
        }

        // Step 3: Check session expiration if timeout is configured
        if ($this->session_timeout > 0 && $this->session->has('authenticated_at')) {
            if (!$this->isSessionValid()) {
                return $this->handleAuthenticationFailure(
                    $request,
                    'session_expired',
                    'Session has expired'
                );
            }
        }

        // Step 4: Build AuthorizedUser from session data
        $user_id = $this->session->get('user_id');
        $username = $this->session->get('username');
        $roles = $this->session->has('roles') ? (array) $this->session->get('roles') : [];
        $permissions = $this->session->has('permissions') ? (array) $this->session->get('permissions') : [];

        // Collect additional user data
        $user_data = [];
        foreach ($this->session as $key => $value) {
            if (!\in_array($key, ['user_id', 'username', 'auth', 'authenticated_at', 'roles', 'permissions'], true)) {
                $user_data[$key] = $value;
            }
        }

        $authorized_user = new AuthorizedUser($user_id, $username, $roles, $permissions, $user_data);

        // Step 5: Attach AuthorizedUser to request
        $request = $request->withAttribute('authorized_user', $authorized_user);

        // Step 6: Log successful session validation
        $this->logger->info('Session validated successfully', [
            'user_id' => $user_id,
            'username' => $username,
            'roles' => $roles,
        ]);

        // Step 7: Dispatch session validation event
        $this->event_bus->dispatch(new Event(
            'user.session.validated',
            self::class,
            [
                'user_id' => $user_id,
                'username' => $username,
                'roles' => $roles,
                'ip' => $this->getClientIp($request),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]
        ));

        // Step 8: Pass to next middleware with authorized user
        return $handler->handle($request);
    }

    /**
     * Check if session is still valid (not expired)
     *
     * @return bool
     */
    private function isSessionValid(): bool
    {
        $authenticated_at = $this->session->get('authenticated_at');

        if (empty($authenticated_at)) {
            return false;
        }

        try {
            $auth_time = new \DateTimeImmutable($authenticated_at);
            $now = new \DateTimeImmutable();
            $diff = $now->getTimestamp() - $auth_time->getTimestamp();

            return $diff <= $this->session_timeout;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse session timestamp', [
                'timestamp' => $authenticated_at,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle authentication failure
     *
     * @param ServerRequestInterface $request        HTTP request
     * @param string                 $failure_reason  Reason for failure
     * @param string                 $log_message     Message to log
     *
     * @return ResponseInterface 401 Unauthorized response
     */
    private function handleAuthenticationFailure(
        ServerRequestInterface $request,
        string $failure_reason,
        string $log_message
    ): ResponseInterface {
        // Log failure
        $this->logger->warning($log_message, [
            'reason' => $failure_reason,
            'ip' => $this->getClientIp($request),
        ]);

        // Dispatch event
        $this->event_bus->dispatch(new Event(
            'user.session.invalid',
            self::class,
            [
                'reason' => $failure_reason,
                'ip' => $this->getClientIp($request),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]
        ));

        // Return 401 response
        $response = $this->response_factory->createResponse(401);
        $response->getBody()->write('Unauthorized: ' . $log_message);

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
