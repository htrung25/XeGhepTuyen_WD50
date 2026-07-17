<?php

namespace App\SwaggerProcessors;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;

/**
 * Adds a documented baseline operation for Laravel API routes that do not yet
 * have an explicit swagger-php operation. Explicit OA attributes always win.
 */
class LaravelRouteProcessor
{
    /** @var array<string, class-string<OA\Operation>> */
    private const OPERATION_CLASSES = [
        'delete' => OA\Delete::class,
        'get' => OA\Get::class,
        'patch' => OA\Patch::class,
        'post' => OA\Post::class,
        'put' => OA\Put::class,
    ];

    public function __invoke(Analysis $analysis): void
    {
        $paths = $this->indexPaths($analysis);

        foreach (RouteFacade::getRoutes() as $route) {
            if (! $this->shouldDocument($route)) {
                continue;
            }

            $path = '/'.$route->uri();
            $pathItem = $paths[$path] ?? new OA\PathItem([
                'path' => $path,
                '_context' => new Context(['generated' => true]),
            ]);

            foreach ($this->httpMethods($route) as $method) {
                if (! Generator::isDefault($pathItem->{$method})) {
                    continue;
                }

                $operationClass = self::OPERATION_CLASSES[$method];
                $pathItem->{$method} = new $operationClass([
                    'operationId' => $this->operationId($route, $method),
                    'summary' => $this->summary($route),
                    'tags' => [$this->tag($route)],
                    'security' => $this->requiresAuthentication($route)
                        ? [['sanctum' => []]]
                        : [],
                    'parameters' => $this->pathParameters($route),
                    'responses' => $this->responses($route),
                    '_context' => new Context(['generated' => true]),
                ]);
            }

            if (! isset($paths[$path])) {
                $analysis->openapi->paths[] = $pathItem;
                $paths[$path] = $pathItem;
            }
        }
    }

    /** @return array<string, OA\PathItem> */
    private function indexPaths(Analysis $analysis): array
    {
        $paths = [];

        if (Generator::isDefault($analysis->openapi->paths)) {
            $analysis->openapi->paths = [];

            return $paths;
        }

        foreach ($analysis->openapi->paths as $pathItem) {
            $paths[$pathItem->path] = $pathItem;
        }

        return $paths;
    }

    private function shouldDocument(Route $route): bool
    {
        if (! str_starts_with($route->uri(), 'api/')) {
            return false;
        }

        if (in_array($route->uri(), ['api/docs', 'api/oauth2-callback', 'api/broadcasting/auth'], true)) {
            return false;
        }

        $action = $route->getActionName();

        return $action === 'Closure' || str_starts_with($action, 'App\\Http\\Controllers\\');
    }

    /** @return list<string> */
    private function httpMethods(Route $route): array
    {
        return array_values(array_filter(
            array_map('strtolower', $route->methods()),
            fn (string $method): bool => isset(self::OPERATION_CLASSES[$method])
        ));
    }

    private function requiresAuthentication(Route $route): bool
    {
        return collect($route->gatherMiddleware())
            ->contains(fn (string $middleware): bool => str_starts_with($middleware, 'auth:'));
    }

    /** @return list<OA\Parameter> */
    private function pathParameters(Route $route): array
    {
        return array_map(
            fn (string $name): OA\Parameter => new OA\Parameter([
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => new OA\Schema(['type' => 'string']),
                '_context' => new Context(['generated' => true]),
            ]),
            $route->parameterNames()
        );
    }

    /** @return list<OA\Response> */
    private function responses(Route $route): array
    {
        $responses = [new OA\Response([
            'response' => 'default',
            'description' => 'Response theo contract của endpoint.',
            '_context' => new Context(['generated' => true]),
        ])];

        if ($this->requiresAuthentication($route)) {
            $responses[] = new OA\Response([
                'response' => 401,
                'description' => 'Chưa xác thực.',
                '_context' => new Context(['generated' => true]),
            ]);
            $responses[] = new OA\Response([
                'response' => 403,
                'description' => 'Không có quyền truy cập.',
                '_context' => new Context(['generated' => true]),
            ]);
        }

        return $responses;
    }

    private function operationId(Route $route, string $method): string
    {
        $action = str_replace(['\\', '@'], '.', $route->getActionName());

        return strtolower($method.'.'.$action.'.'.substr(sha1($route->uri()), 0, 8));
    }

    private function summary(Route $route): string
    {
        $action = class_basename(str_replace('@', '\\', $route->getActionName()));

        return $action === 'Closure'
            ? 'Kiểm tra trạng thái hệ thống'
            : trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $action));
    }

    private function tag(Route $route): string
    {
        $segments = explode('/', $route->uri());
        $actor = ucfirst($segments[1] ?? 'API');
        $resource = str_replace('-', ' ', $segments[2] ?? 'General');

        return $actor.' '.ucwords($resource);
    }
}
