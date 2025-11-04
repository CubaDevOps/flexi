<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Controllers;

use CubaDevOps\Flexi\Contracts\Classes\HttpHandler;
use CubaDevOps\Flexi\Contracts\Interfaces\EventBusInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\SessionStorageInterface;
use CubaDevOps\Flexi\Domain\Events\Event;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * LoginController - Handles user authentication and login
 *
 * This controller expects the BasicAuthMiddleware to have already
 * authenticated the request. If this controller is called, the user
 * is guaranteed to be authenticated.
 *
 * POST /auth/login
 *
 * Request:
 *   Authorization: Basic base64(username:password)
 *
 * Response on success (200):
 *   {
 *     "success": true,
 *     "user_id": 1,
 *     "username": "admin",
 *     "authenticated_at": "2025-10-27T...",
 *     "user_data": {...}
 *   }
 */
final class LoginController extends HttpHandler
{
    private SessionStorageInterface $session;
    private EventBusInterface $event_bus;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        SessionStorageInterface $session,
        EventBusInterface $event_bus
    ) {
        parent::__construct($response_factory);
        $this->session = $session;
        $this->event_bus = $event_bus;
    }

    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        // User is already authenticated by BasicAuthMiddleware
        // Extract authenticated session data
        $user_id = $this->session->get('user_id');
        $username = $this->session->get('username');
        $authenticated_at = $this->session->get('authenticated_at');

        // Prepare response data
        $response_data = [
            'success' => true,
            'message' => 'User authenticated successfully',
            'user_id' => $user_id,
            'username' => $username,
            'authenticated_at' => $authenticated_at,
        ];

        // Include any additional user data from session
        $user_data = [];
        foreach ($this->session as $key => $value) {
            if (!\in_array($key, ['user_id', 'username', 'authenticated_at', 'auth'], true)) {
                $user_data[$key] = $value;
            }
        }

        if (!empty($user_data)) {
            $response_data['user_data'] = $user_data;
        }

        // Dispatch login success event
        $this->event_bus->dispatch(new Event(
            'user.login.success',
            self::class,
            [
                'user_id' => $user_id,
                'username' => $username,
                'ip' => $this->getClientIp($request),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]
        ));

        // Build response
        $response = $this->createResponse(200);
        $response->getBody()->write(json_encode($response_data, JSON_THROW_ON_ERROR));

        // Add JSON content type header
        return $response->withHeader('Content-Type', 'application/json');
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
