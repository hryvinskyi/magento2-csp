<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Serializer;

use Hryvinskyi\Csp\Api\Serializer\CspPolicySerializerInterface;
use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Psr\Log\LoggerInterface;

class CspPolicySerializer implements CspPolicySerializerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function serialize(array $policies): array
    {
        $serialized = [];

        foreach ($policies as $key => $policy) {
            try {
                if ($policy instanceof PolicyInterface) {
                    $serialized[$key] = $this->serializePolicy($policy);
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to serialize CSP policy', [
                    'key' => $key,
                    'policy_class' => get_class($policy),
                    'exception' => $e->getMessage()
                ]);
            }
        }

        return $serialized;
    }

    /**
     * @inheritDoc
     */
    public function unserialize(array $data): array
    {
        $policies = [];

        foreach ($data as $key => $policyData) {
            try {
                if (is_array($policyData)) {
                    $policy = $this->unserializePolicy($policyData);
                    if ($policy) {
                        $policies[$key] = $policy;
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to unserialize CSP policy', [
                    'key' => $key,
                    'policy_data' => $policyData,
                    'exception' => $e->getMessage()
                ]);
            }
        }

        return $policies;
    }

    /**
     * Serialize a single policy
     *
     * @param PolicyInterface $policy
     * @return array<string, mixed>
     */
    private function serializePolicy(PolicyInterface $policy): array
    {
        $data = [
            'class' => get_class($policy),
            'id' => $policy->getId(),
        ];

        // Handle FetchPolicy specifically
        if ($policy instanceof FetchPolicy) {
            $data['policy_type'] = 'fetch';
            $data['directive'] = $this->getFetchPolicyDirective($policy);
            $data['self_allowed'] = $this->isFetchPolicySelfAllowed($policy);
            $data['hosts'] = $this->getFetchPolicyHosts($policy);
            $data['hashes'] = $this->getFetchPolicyHashes($policy);
        } else {
            // Generic policy serialization
            $data['policy_type'] = 'generic';
            $data['policy_data'] = $this->getGenericPolicyData($policy);
        }

        return $data;
    }

    /**
     * Unserialize a single policy
     *
     * @param array<string, mixed> $data
     * @return PolicyInterface|null
     */
    private function unserializePolicy(array $data): ?PolicyInterface
    {
        if (!isset($data['policy_type'])) {
            return null;
        }

        switch ($data['policy_type']) {
            case 'fetch':
                return $this->createFetchPolicy($data);
            case 'generic':
                return $this->createGenericPolicy($data);
            default:
                return null;
        }
    }

    /**
     * Create FetchPolicy from serialized data
     *
     * @param array<string, mixed> $data
     * @return FetchPolicy|null
     */
    private function createFetchPolicy(array $data): ?FetchPolicy
    {
        if (!isset($data['id'], $data['self_allowed'], $data['hosts'])) {
            return null;
        }

        $hashes = $data['hashes'] ?? [];

        return new FetchPolicy(
            $data['id'],                 // id (use the id field, not directive)
            false,                       // noneAllowed (default to false for regular policies)
            $data['hosts'],              // hostSources
            [],                          // schemeSources (empty by default)
            $data['self_allowed'],       // selfAllowed
            false,                       // inlineAllowed (default false)
            false,                       // evalAllowed (default false)
            [],                          // nonceValues (empty by default)
            $hashes,                     // hashValues
            false,                       // dynamicAllowed (default false)
            false                        // eventHandlersAllowed (default false)
        );
    }

    /**
     * Create generic policy from serialized data
     *
     * @param array<string, mixed> $data
     * @return PolicyInterface|null
     */
    private function createGenericPolicy(array $data): ?PolicyInterface
    {
        // For now, we only support FetchPolicy
        // This can be extended in the future for other policy types
        return null;
    }

    /**
     * Extract directive from FetchPolicy using reflection
     *
     * @param FetchPolicy $policy
     * @return string
     */
    private function getFetchPolicyDirective(FetchPolicy $policy): string
    {
        // The directive is actually stored in the 'id' property
        return $policy->getId();
    }

    /**
     * Extract self allowed flag from FetchPolicy using reflection
     *
     * @param FetchPolicy $policy
     * @return bool
     */
    private function isFetchPolicySelfAllowed(FetchPolicy $policy): bool
    {
        return $policy->isSelfAllowed();
    }

    /**
     * Extract hosts from FetchPolicy using reflection
     *
     * @param FetchPolicy $policy
     * @return array<string>
     */
    private function getFetchPolicyHosts(FetchPolicy $policy): array
    {
        return $policy->getHostSources();
    }

    /**
     * Extract hashes from FetchPolicy using reflection
     *
     * @param FetchPolicy $policy
     * @return array<string>
     */
    private function getFetchPolicyHashes(FetchPolicy $policy): array
    {
        return $policy->getHashes();
    }

    /**
     * Get generic policy data (placeholder for future extensions)
     *
     * @param PolicyInterface $policy
     * @return array<string, mixed>
     */
    private function getGenericPolicyData(PolicyInterface $policy): array
    {
        return [
            'id' => $policy->getId()
        ];
    }
}