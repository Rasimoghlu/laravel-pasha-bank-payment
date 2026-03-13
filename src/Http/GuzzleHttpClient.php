<?php

namespace Sarkhanrasimoghlu\PashaBank\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Sarkhanrasimoghlu\PashaBank\Contracts\ConfigurationInterface;
use Sarkhanrasimoghlu\PashaBank\Contracts\HttpClientInterface;
use Sarkhanrasimoghlu\PashaBank\Exceptions\HttpException;

class GuzzleHttpClient implements HttpClientInterface
{
    private Client $client;

    public function __construct(
        private readonly ConfigurationInterface $configuration,
        ?Client $client = null,
    ) {
        $this->client = $client ?? $this->createClient();
    }

    public function post(array $params): array
    {
        $url = $this->configuration->getMerchantHandler();

        try {
            $response = $this->client->post($url, [
                'form_params' => $params,
            ]);

            $body = (string) $response->getBody();

            return $this->parseResponse($body);
        } catch (ConnectException $e) {
            if (str_contains($e->getMessage(), 'SSL') || str_contains($e->getMessage(), 'certificate')) {
                throw HttpException::sslError($e->getMessage(), $e);
            }
            throw HttpException::connectionFailed($url, $e);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $body = (string) $e->getResponse()->getBody();
                throw HttpException::serverError($statusCode, $body);
            }
            throw HttpException::connectionFailed($url, $e);
        }
    }

    /**
     * Parse Pasha Bank's key-value response format.
     * Example: "TRANSACTION_ID: TwXcbhBgrIsMY0A7s982nx/pSzE="
     *
     * @return array<string, string>
     */
    private function parseResponse(string $body): array
    {
        $result = [];
        $lines = explode("\n", trim($body));

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $colonPos));
            $value = trim(substr($line, $colonPos + 1));

            if ($key !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function createClient(): Client
    {
        $sslVerify = $this->configuration->getSslVerify();

        $options = [
            'timeout' => $this->configuration->getTimeout(),
        ];

        // Only enforce TLS 1.2 and certificate options when SSL is enabled
        if ($sslVerify) {
            $options['curl'] = [
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ];

            $cert = $this->configuration->getCertificate();
            $certPassword = $this->configuration->getCertificatePassword();
            $privateKey = $this->configuration->getPrivateKey();
            $privateKeyPassword = $this->configuration->getPrivateKeyPassword();
            $caCert = $this->configuration->getCaCertificate();

            if (!empty($cert)) {
                $extension = strtolower(pathinfo($cert, PATHINFO_EXTENSION));

                if ($extension === 'p12' || $extension === 'pfx') {
                    $options['curl'][CURLOPT_SSLCERT] = $cert;
                    $options['curl'][CURLOPT_SSLCERTTYPE] = 'P12';
                    if (!empty($certPassword)) {
                        $options['curl'][CURLOPT_SSLCERTPASSWD] = $certPassword;
                    }
                } else {
                    $options['cert'] = !empty($certPassword)
                        ? [$cert, $certPassword]
                        : $cert;
                }
            }

            if (!empty($privateKey)) {
                $options['ssl_key'] = !empty($privateKeyPassword)
                    ? [$privateKey, $privateKeyPassword]
                    : $privateKey;
                $options['curl'][CURLOPT_SSLKEYTYPE] = 'PEM';
            }

            if (!empty($caCert)) {
                $options['verify'] = $caCert;
            } else {
                $options['verify'] = true;
            }
        } else {
            $options['verify'] = false;
        }

        return new Client($options);
    }
}
