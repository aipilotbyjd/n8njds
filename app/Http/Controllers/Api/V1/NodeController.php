<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NodeController extends Controller
{
    public function index(Request $request)
    {
        // Return available node types
        $nodeTypes = [
            [
                'id' => 'manual-trigger',
                'name' => 'Manual Trigger',
                'description' => 'A trigger that can be manually activated',
                'type' => 'trigger',
                'icon' => 'trigger',
            ],
            [
                'id' => 'http-request',
                'name' => 'HTTP Request',
                'description' => 'Make an HTTP request to a specified URL',
                'type' => 'regular',
                'icon' => 'http',
            ],
            [
                'id' => 'log',
                'name' => 'Log',
                'description' => 'Log data for debugging purposes',
                'type' => 'output',
                'icon' => 'log',
            ]
        ];

        return response()->json(['nodes' => $nodeTypes]);
    }

    public function config(Request $request, string $nodeId)
    {
        // Return configuration schema for a specific node type
        $config = match($nodeId) {
            'manual-trigger' => [
                'parameters' => [],
                'outputs' => ['data'],
            ],
            'http-request' => [
                'parameters' => [
                    ['name' => 'url', 'type' => 'string', 'required' => true],
                    ['name' => 'method', 'type' => 'select', 'options' => ['GET', 'POST', 'PUT', 'DELETE']],
                    ['name' => 'headers', 'type' => 'object'],
                    ['name' => 'body', 'type' => 'object'],
                ],
                'outputs' => ['response', 'status_code'],
            ],
            'log' => [
                'parameters' => [
                    ['name' => 'level', 'type' => 'select', 'options' => ['info', 'warning', 'error', 'debug']],
                    ['name' => 'message', 'type' => 'string', 'required' => true],
                ],
                'outputs' => ['data'],
            ],
            default => ['parameters' => [], 'outputs' => []]
        };

        return response()->json(['config' => $config]);
    }
}