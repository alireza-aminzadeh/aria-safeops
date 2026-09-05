<?php

namespace App\Infrastructure\AiGateway;

final class AiGatewayFactory
{
    public function __construct(
        private readonly NullAiGatewayAdapter $null,
        private readonly OnPremAiGatewayAdapter $onPrem,
        private readonly HttpAiGatewayAdapter $http,
    ) {
    }

    public function create(): AiGatewayInterface
    {
        if (getenv('AI_GATEWAY_ENABLED') !== 'true') {
            return $this->null;
        }
        if ($this->http->isEnabled()) {
            return $this->http;
        }

        return $this->onPrem;
    }
}
