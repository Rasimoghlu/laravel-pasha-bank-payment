<?php

namespace Sarkhanrasimoghlu\PashaBank\Contracts;

interface HttpClientInterface
{
    /**
     * Send a POST request to the Merchant Handler with form-encoded parameters.
     * Returns parsed key-value response as associative array.
     *
     * @param array<string, string> $params
     * @return array<string, string>
     */
    public function post(array $params): array;
}
