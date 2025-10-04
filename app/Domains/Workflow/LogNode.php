<?php

namespace App\Workflows\Nodes;

class LogNode extends BaseNode
{
    public function execute(array $input): array
    {
        $logLevel = $this->parameters['level'] ?? 'info';
        $message = $this->parameters['message'] ?? json_encode($input);
        
        // Log the message based on the level
        match($logLevel) {
            'error' => \Log::error($message),
            'warning' => \Log::warning($message),
            'info' => \Log::info($message),
            'debug' => \Log::debug($message),
            default => \Log::info($message),
        };

        return [
            'status' => 'success',
            'data' => $input,
            'log_level' => $logLevel,
            'node_id' => $this->getId(),
            'node_type' => $this->getType(),
        ];
    }

    public function validate(array $config): bool
    {
        $validLevels = ['debug', 'info', 'warning', 'error'];
        $level = $config['level'] ?? 'info';
        
        return in_array($level, $validLevels);
    }

    public function getType(): string
    {
        return 'log';
    }
}