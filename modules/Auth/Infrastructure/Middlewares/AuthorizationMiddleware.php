<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Middlewares;

use CubaDevOps\Flexi\Contracts\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Domain\Events\Event;
use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\AuthorizationProviderInterface;
use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\AuthorizedUser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * AuthorizationMiddleware - Verifies user has required permissions/roles
 *
 * This middleware is executed AFTER AuthCheckMiddleware and verifies
 * that the authenticated user has the necessary authorization to
 * access the requested resource.
 *
 * It supports multiple authorization strategies:
 * - Role-Based Access Control (RBAC)
 * - Permission-Based Access Control (PBAC)
 * - Combination of both
 *
 * Authorization requirements can be specified via:
 * 1. Request attributes (set by routing system)
 * 2. Route configuration
 * 3. Custom headers
 *
 * If authorization succeeds:
 * - Logs the authorization decision
 * - Dispatches 'user.authorized' event
 * - Passes to next middleware
 *
 * If authorization fails:
 * - Logs the denial
 * - Dispatches 'user.authorization.denied' event
 * - Returns 403 Forbidden
 */
final class AuthorizationMiddleware implements MiddlewareInterface
{
    private AuthorizationProviderInterface $authorization_provider;
    private ResponseFactoryInterface $response_factory;
    private EventBusInterface $event_bus;
    private LoggerInterface $logger;

    public function __construct(
        AuthorizationProviderInterface $authorization_provider,
        ResponseFactoryInterface $response_factory,
        EventBusInterface $event_bus,
        LoggerInterface $logger
    ) {
        $this->authorization_provider = $authorization_provider;
        $this->response_factory = $response_factory;
        $this->event_bus = $event_bus;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Step 1: Get AuthorizedUser from request (set by AuthCheckMiddleware)
        $authorized_user = $request->getAttribute('authorized_user');

        if (!($authorized_user instanceof AuthorizedUser)) {
            $this->logger->error('AuthorizedUser not found in request', [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ]);

            return $this->handleAuthorizationFailure(
                $request,
                null,
                'missing_authorized_user',
                'AuthorizedUser not found in request context'
            );
        }

        // Step 2: Extract authorization requirements from request
        $required_roles = $this->getRequiredRoles($request);
        $required_permissions = $this->getRequiredPermissions($request);

        // If no requirements are specified, authorization is not required
        if (empty($required_roles) && empty($required_permissions)) {
            $this->logger->debug('No authorization requirements for this request', [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ]);

            return $handler->handle($request);
        }

        // Step 3: Check authorization
        $is_authorized = $this->authorization_provider->authorize(
            $authorized_user->getUserData(),
            $required_roles,
            $required_permissions
        );

        if (!$is_authorized) {
            return $this->handleAuthorizationFailure(
                $request,
                $authorized_user,
                'insufficient_privileges',
                sprintf('User %s lacks required authorization', $authorized_user->getUsername())
            );
        }

        // Step 4: Log successful authorization
        $this->logger->info('User authorization granted', [
            'user_id' => $authorized_user->getUserId(),
            'username' => $authorized_user->getUsername(),
            'required_roles' => $required_roles,
            'required_permissions' => $required_permissions,
            'user_roles' => $authorized_user->getRoles(),
            'user_permissions' => $authorized_user->getPermissions(),
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
        ]);

        // Step 5: Dispatch authorization success event
        $this->event_bus->dispatch(new Event(
            'user.authorized',
            self::class,
            [
                'user_id' => $authorized_user->getUserId(),
                'username' => $authorized_user->getUsername(),
                'required_roles' => $required_roles,
                'required_permissions' => $required_permissions,
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'ip' => $this->getClientIp($request),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]
        ));

        // Step 6: Pass to next middleware
        return $handler->handle($request);
    }

    /**
     * Extract required roles from request attributes or headers
     *
     * Priority:
     * 1. Request attribute 'required_roles'
     * 2. Header 'X-Required-Roles' (comma-separated)
     * 3. Empty array (no roles required)
     *
     * @param ServerRequestInterface $request HTTP request
     *
     * @return array Required roles
     */
    private function getRequiredRoles(ServerRequestInterface $request): array
    {
        // Check request attribute first
        $required_roles = $request->getAttribute('required_roles');
        if (\is_array($required_roles)) {
            return $required_roles;
        }

        // Check header second
        $roles_header = $request->getHeaderLine('X-Required-Roles');
        if (!empty($roles_header)) {
            return array_map('trim', explode(',', $roles_header));
        }

        return [];
    }

    /**
     * Extract required permissions from request attributes or headers
     *
     * Priority:
     * 1. Request attribute 'required_permissions'
     * 2. Header 'X-Required-Permissions' (comma-separated)
     * 3. Empty array (no permissions required)
     *
     * @param ServerRequestInterface $request HTTP request
     *
     * @return array Required permissions
     */
    private function getRequiredPermissions(ServerRequestInterface $request): array
    {
        // Check request attribute first
        $required_permissions = $request->getAttribute('required_permissions');
        if (\is_array($required_permissions)) {
            return $required_permissions;
        }

        // Check header second
        $permissions_header = $request->getHeaderLine('X-Required-Permissions');
        if (!empty($permissions_header)) {
            return array_map('trim', explode(',', $permissions_header));
        }

        return [];
    }

    /**
     * Handle authorization failure
     *
     * @param ServerRequestInterface  $request           HTTP request
     * @param AuthorizedUser|null     $authorized_user   Authorized user (null if not found)
     * @param string                  $denial_reason     Reason for denial
     * @param string                  $log_message       Message to log
     *
     * @return ResponseInterface 403 Forbidden response
     */
    private function handleAuthorizationFailure(
        ServerRequestInterface $request,
        $authorized_user,
        string $denial_reason,
        string $log_message
    ): ResponseInterface {
        // Log denial
        $context = [
            'reason' => $denial_reason,
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp($request),
        ];

        if ($authorized_user instanceof AuthorizedUser) {
            $context['user_id'] = $authorized_user->getUserId();
            $context['username'] = $authorized_user->getUsername();
        }

        $this->logger->warning($log_message, $context);

        // Dispatch event
        $event_data = [
            'reason' => $denial_reason,
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp($request),
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        if ($authorized_user instanceof AuthorizedUser) {
            $event_data['user_id'] = $authorized_user->getUserId();
            $event_data['username'] = $authorized_user->getUsername();
        }

        $this->event_bus->dispatch(new Event(
            'user.authorization.denied',
            self::class,
            $event_data
        ));

        // Return 403 response
        $response = $this->response_factory->createResponse(403);
        $response->getBody()->write('Forbidden: ' . $log_message);

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
