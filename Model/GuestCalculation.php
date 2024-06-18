<?php
/**
 * Copyright (c) 2024 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Model;

use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\IsActive;
use Space\FreeShippingRemainingCost\Api\Data\RemainingCostInterfaceFactory;
use Space\FreeShippingRemainingCost\Model\Service\InfoProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Space\FreeShippingRemainingCost\Api\Data\RemainingCostInterface;

class GuestCalculation
{
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $cartRepository;

    /**
     * @var IsActive
     */
    private IsActive $isActive;

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
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     * @param IsActive $isActive
     * @param RemainingCostInterfaceFactory $remainingCostCalculationFactory
     * @param InfoProvider $infoProvider
     */
    public function __construct(
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository,
        IsActive $isActive,
        RemainingCostInterfaceFactory $remainingCostCalculationFactory,
        InfoProvider $infoProvider
    ) {
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
        $this->isActive = $isActive;
        $this->remainingCostCalculationFactory = $remainingCostCalculationFactory;
        $this->infoProvider = $infoProvider;
    }

    /**
     * Execute
     *
     * @param string $cartHash
     * @param int|null $customerId
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws GraphQlAuthorizationException
     */
    public function execute(string $cartHash, ?int $customerId): array
    {
        try {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($cartHash);
            /** @var Quote $cart */
            $cart = $this->cartRepository->get($cartId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __('Could not find a cart with ID "%masked_cart_id"', ['masked_cart_id' => $cartHash])
            );
        }

        if (false === (bool)$this->isActive->execute($cart)) {
            throw new GraphQlNoSuchEntityException(__('The cart isn\'t active.'));
        }

        $cartCustomerId = (int)$cart->getCustomerId();

        /* Guest cart, allow operations */
        if (0 === $cartCustomerId && (null === $customerId || 0 === $customerId)) {
            return $this->calculate($cart);
        }

        if ($cartCustomerId !== $customerId) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current user cannot perform operations on cart "%masked_cart_id"',
                    ['masked_cart_id' => $cartHash]
                )
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
    private function calculate(Quote $quote): array
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
