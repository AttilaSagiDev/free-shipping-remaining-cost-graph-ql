<?php
/**
 * Copyright (c) 2024 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Model;

use Space\FreeShippingRemainingCost\Api\Data\RemainingCostInterfaceFactory;
use Space\FreeShippingRemainingCost\Model\Service\InfoProvider;
use Magento\Quote\Model\Quote;
use Space\FreeShippingRemainingCost\Api\Data\RemainingCostInterface;

class CalculationProvider
{
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
     * @param RemainingCostInterfaceFactory $remainingCostCalculationFactory
     * @param InfoProvider $infoProvider
     */
    public function __construct(
        RemainingCostInterfaceFactory $remainingCostCalculationFactory,
        InfoProvider $infoProvider
    ) {
        $this->remainingCostCalculationFactory = $remainingCostCalculationFactory;
        $this->infoProvider = $infoProvider;
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
