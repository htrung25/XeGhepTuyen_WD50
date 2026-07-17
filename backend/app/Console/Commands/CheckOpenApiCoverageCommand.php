<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use JsonException;

class CheckOpenApiCoverageCommand extends Command
{
    protected $signature = 'openapi:check-coverage';

    protected $description = 'Ensure every application API route exists in the generated OpenAPI document';

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $documentPath = storage_path('api-docs/api-docs.json');

        if (! is_file($documentPath)) {
            $this->error('OpenAPI document not found. Run l5-swagger:generate first.');

            return self::FAILURE;
        }

        $document = json_decode(
            file_get_contents($documentPath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $missing = [];
        $expectedOperations = 0;

        foreach (RouteFacade::getRoutes() as $route) {
            if (! $this->shouldCheck($route)) {
                continue;
            }

            $path = '/'.$route->uri();
            foreach ($this->methods($route) as $method) {
                $expectedOperations++;
                if (! isset($document['paths'][$path][$method])) {
                    $missing[] = strtoupper($method).' '.$path;
                }
            }
        }

        if ($missing !== []) {
            $this->error('OpenAPI coverage is incomplete:');
            foreach ($missing as $operation) {
                $this->line(' - '.$operation);
            }

            return self::FAILURE;
        }

        $this->info("OpenAPI coverage passed: {$expectedOperations} operations documented.");

        return self::SUCCESS;
    }

    private function shouldCheck(Route $route): bool
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
    private function methods(Route $route): array
    {
        return array_values(array_intersect(
            array_map('strtolower', $route->methods()),
            ['get', 'post', 'put', 'patch', 'delete']
        ));
    }
}
