<?php
/**
 * Copyright (c) 2024 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Model;

use Magento\Quote\Api\CartManagementInterface;
use Space\FreeShippingRemainingCost\Api\Data\RemainingCostInterfaceFactory;
use Space\FreeShippingRemainingCost\Model\Service\InfoProvider;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Space\FreeShippingRemainingCost\Api\Data\RemainingCostInterface;

class CustomerCalculation
{
    /**
     * @var CartManagementInterface
     */
    private CartManagementInterface $cartManagement;

    /**
     * @var RemainingCostInterfaceFactory
     */
    private RemainingCostInterfaceFactory $remainingCostCalculationFactory;

    /**
     * @var InfoProvider
     */
    private InfoProvider $infoProvider;

    /**
     * Constructor
     *
     * @param CartManagementInterface $cartManagement
     * @param RemainingCostInterfaceFactory $remainingCostCalculationFactory
     * @param InfoProvider $infoProvider
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        RemainingCostInterfaceFactory $remainingCostCalculationFactory,
        InfoProvider $infoProvider
    ) {
        $this->cartManagement = $cartManagement;
        $this->remainingCostCalculationFactory = $remainingCostCalculationFactory;
        $this->infoProvider = $infoProvider;
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

        return $this->calculate($cart);
    }

    /**
     * Calculate
     *
     * @param Quote $quote
     * @return array
     */
    public function calculate(Quote $quote): array
    {
        $remainingCost = $this->remainingCostCalculationFactory->create();

        $subtotal = $quote->getShippingAddress()->getSubtotalWithDiscount();
        $remainingCostValue = $this->infoProvider->getRemainingCostValue($quote, $subtotal);
        $remainingCost->setMessage($this->infoProvider->getMessage($remainingCostValue, $subtotal));
        $remainingCost->setValue($remainingCostValue);

        return [
            RemainingCostInterface::MESSAGE => $remainingCost->getMessage(),
            RemainingCostInterface::VALUE => $remainingCost->getValue()
        ];
    }
}
