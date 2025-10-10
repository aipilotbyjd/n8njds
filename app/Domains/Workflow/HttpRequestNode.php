<?php

namespace App\Workflows\Nodes;

use Illuminate\Support\Facades\Http;

class HttpRequestNode extends BaseNode
{
    public function execute(array $input): array
    {
        $url = $this->parameters['url'] ?? $input['url'] ?? null;
        $method = $this->parameters['method'] ?? $input['method'] ?? 'GET';
        $headers = $this->parameters['headers'] ?? $input['headers'] ?? [];
        $body = $this->parameters['body'] ?? $input['body'] ?? [];

        if (! $url) {
            return [
                'status' => 'error',
                'message' => 'URL is required',
                'node_id' => $this->getId(),
                'node_type' => $this->getType(),
            ];
        }

        try {
            $response = Http::withHeaders($headers)
                ->{$method}($url, $body);

            return [
                'status' => 'success',
                'data' => $response->json(),
                'http_status_code' => $response->status(),
                'node_id' => $this->getId(),
                'node_type' => $this->getType(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'node_id' => $this->getId(),
                'node_type' => $this->getType(),
            ];
        }
    }

    public function validate(array $config): bool
    {
        $required = ['url'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                return false;
            }
        }

        return true;
    }

    public function getType(): string
    {
        return 'http-request';
    }
}
