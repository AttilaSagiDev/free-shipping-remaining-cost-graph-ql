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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

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
     * @var CalculationProvider
     */
    private CalculationProvider $calculationProvider;

    /**
     * Constructor
     *
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     * @param IsActive $isActive
     * @param CalculationProvider $calculationProvider
     */
    public function __construct(
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository,
        IsActive $isActive,
        CalculationProvider $calculationProvider
    ) {
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
        $this->isActive = $isActive;
        $this->calculationProvider = $calculationProvider;
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
            return $this->calculationProvider->calculate($cart);
        }

        if ($cartCustomerId !== $customerId) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current user cannot perform operations on cart "%masked_cart_id"',
                    ['masked_cart_id' => $cartHash]
                )
            );
        }

        return $this->calculationProvider->calculate($cart);
    }
}
