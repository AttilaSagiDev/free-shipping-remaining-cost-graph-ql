<?php
/**
 * Copyright (c) 2024 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Model;

use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

class CustomerCalculation
{
    /**
     * @var CartManagementInterface
     */
    private CartManagementInterface $cartManagement;

    /**
     * @var CalculationProvider
     */
    private CalculationProvider $calculationProvider;

    /**
     * Constructor
     *
     * @param CartManagementInterface $cartManagement
     * @param CalculationProvider $calculationProvider
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        CalculationProvider $calculationProvider
    ) {
        $this->cartManagement = $cartManagement;
        $this->calculationProvider = $calculationProvider;
    }

    /**
     * Execute
     *
     * @param int $customerId
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    public function execute(int $customerId): array
    {
        try {
            /** @var Quote $cart */
            $cart = $this->cartManagement->getCartForCustomer($customerId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find a cart for customer "%1".', $customerId)
            );
        }

        return $this->calculationProvider->calculate($cart);
    }
}
