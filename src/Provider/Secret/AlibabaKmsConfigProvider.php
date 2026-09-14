<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use AlibabaCloud\Dara\Models\RuntimeOptions;
use AlibabaCloud\SDK\Kms\V20160120\Kms;
use AlibabaCloud\SDK\Kms\V20160120\Models\GetSecretValueRequest;
use AlibabaCloud\Tea\Exception\TeaError;
use Lagdo\Facades\Logger;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Exception;
use RuntimeException;

class AlibabaKmsConfigProvider extends SecretConfigProvider
{
    /**
     * @param KeyBuilderInterface $keyBuilder
     * @param Kms $kms
     * @param RuntimeOptions $options
     */
    public function __construct(private KeyBuilderInterface $keyBuilder,
        private Kms $kms, private RuntimeOptions $options)
    {}

    /**
     * @param string $prefix
     * @param string $option
     *
     * @return string
     * @throws RuntimeException
     */
    private function getSecretValue(string $prefix, string $option): string
    {
        $secretName = $this->keyBuilder->build($prefix, $option);
        $request = new GetSecretValueRequest([
            'secretName' => $secretName,
        ]);
        try {
            $response = $this->kms->getSecretValueWithOptions($request, $this->options);
        } catch (Exception $e) {
            Logger::error('Failed to retrieve a secret from Alibaba KMS.', [
                'error' => $e instanceof TeaError ? $e->getErrorInfo() : $e->getMessage(),
            ]);
            throw new RuntimeException("Secret retrieval failed");
        }
        $value = $response->body->secretData;
        if ($value === '') {
            throw new RuntimeException("Secret retrieval failed: empty value");
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function getUsername(string $prefix): string
    {
        return $this->getSecretValue($prefix, 'username');
    }

    /**
     * @inheritDoc
     */
    protected function getPassword(string $prefix): string
    {
        return $this->getSecretValue($prefix, 'password');
    }
}
