<?php
/**
 * Copyright (c) 2024 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Space\FreeShippingRemainingCostGraphQl\Model\GuestCalculation;
use Space\FreeShippingRemainingCost\Api\Data\ConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

class GuestCartRemainingCost implements ResolverInterface
{
    /**
     * @var GuestCalculation
     */
    private GuestCalculation $guestCalculation;

    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * Constructor
     *
     * @param GuestCalculation $guestCalculation
     * @param ConfigInterface $config
     */
    public function __construct(
        GuestCalculation $guestCalculation,
        ConfigInterface $config
    ) {
        $this->guestCalculation = $guestCalculation;
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
     * @throws GraphQlInputException
     * @throws GraphQlAuthorizationException
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

        if (empty($args['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        return $this->guestCalculation->execute($args['cart_id'], $context->getUserId());
    }
}
