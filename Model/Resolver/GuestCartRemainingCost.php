<?php
/**
 * Copyright (c) 2024 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Space\FreeShippingRemainingCostGraphQl\Model\GuestCalculation;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class GuestCartRemainingCost implements ResolverInterface
{
    /**
     * @var GuestCalculation
     */
    private GuestCalculation $guestCalculation;

    /**
     * Constructor
     *
     * @param GuestCalculation $guestCalculation
     */
    public function __construct(
        GuestCalculation $guestCalculation
    ) {
        $this->guestCalculation = $guestCalculation;
    }

    /**
     * Resolve
     *
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (empty($args['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        return $this->guestCalculation->execute($args['cart_id'], $context->getUserId());
    }
}
