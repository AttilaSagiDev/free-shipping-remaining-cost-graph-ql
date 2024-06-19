<?php
/**
 * Copyright (c) 2024 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Space\FreeShippingRemainingCostGraphQl\Model\CustomerCalculation;
use Space\FreeShippingRemainingCost\Api\Data\ConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

class CustomerCartRemainingCost implements ResolverInterface
{
    /**
     * @var CustomerCalculation
     */
    private CustomerCalculation $customerCalculation;

    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * Constructor
     *
     * @param CustomerCalculation $customerCalculation
     * @param ConfigInterface $config
     */
    public function __construct(
        CustomerCalculation $customerCalculation,
        ConfigInterface $config
    ) {
        $this->customerCalculation = $customerCalculation;
        $this->config = $config;
    }

    /**
     * Resolver
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (!$this->config->isEnabled()) {
            throw new GraphQlInputException(__('Space FreeShippingRemainingCost module is not enabled.'));
        }

        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The request is allowed for logged in customer'));
        }

        return $this->customerCalculation->execute($context->getUserId());
    }
}
