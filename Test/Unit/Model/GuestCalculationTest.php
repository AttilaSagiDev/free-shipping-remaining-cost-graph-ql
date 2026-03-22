<?php
/**
 * Copyright (c) 2026 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Space\FreeShippingRemainingCostGraphQl\Model\GuestCalculation;
use Space\FreeShippingRemainingCostGraphQl\Model\CalculationProvider;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\IsActive;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

class GuestCalculationTest extends TestCase
{
    /**
     * @var GuestCalculation
     */
    private GuestCalculation $model;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface|MockObject
     */
    private MaskedQuoteIdToQuoteIdInterface|MockObject $maskedQuoteIdToQuoteIdMock;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    private CartRepositoryInterface|MockObject $cartRepositoryMock;

    /**
     * @var IsActive|MockObject
     */
    private IsActive|MockObject $isActiveMock;

    /**
     * @var CalculationProvider|MockObject
     */
    private CalculationProvider|MockObject $calculationProviderMock;

    protected function setUp(): void
    {
        $this->maskedQuoteIdToQuoteIdMock = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->isActiveMock = $this->createMock(IsActive::class);
        $this->calculationProviderMock = $this->createMock(CalculationProvider::class);

        $this->model = new GuestCalculation(
            $this->maskedQuoteIdToQuoteIdMock,
            $this->cartRepositoryMock,
            $this->isActiveMock,
            $this->calculationProviderMock
        );
    }

    public function testExecuteSuccessGuest(): void
    {
        $cartHash = 'masked_id_123';
        $cartId = 10;
        $expectedResult = ['value' => 20.0, 'message' => 'Test'];

        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId'])
            ->getMock();

        $this->maskedQuoteIdToQuoteIdMock->expects($this->once())
            ->method('execute')
            ->with($cartHash)
            ->willReturn($cartId);

        $this->cartRepositoryMock->expects($this->once())
            ->method('get')
            ->with($cartId)
            ->willReturn($quoteMock);

        $this->isActiveMock->expects($this->once())
            ->method('execute')
            ->with($quoteMock)
            ->willReturn(true);

        $quoteMock->method('getCustomerId')->willReturn(0);

        $this->calculationProviderMock->expects($this->once())
            ->method('calculate')
            ->with($quoteMock)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->model->execute($cartHash, null));
    }

    public function testExecuteThrowsExceptionWhenCartNotFound(): void
    {
        $cartHash = 'invalid_hash';

        $this->maskedQuoteIdToQuoteIdMock->method('execute')
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(GraphQlNoSuchEntityException::class);
        $this->expectExceptionMessage('Could not find a cart with ID "invalid_hash"');

        $this->model->execute($cartHash, null);
    }

    public function testExecuteThrowsExceptionWhenCartInactive(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $this->maskedQuoteIdToQuoteIdMock->method('execute')->willReturn(1);
        $this->cartRepositoryMock->method('get')->willReturn($quoteMock);

        $this->isActiveMock->method('execute')->willReturn(false);

        $this->expectException(GraphQlNoSuchEntityException::class);
        $this->expectExceptionMessage("The cart isn't active.");

        $this->model->execute('hash', null);
    }

    public function testExecuteThrowsAuthorizationException(): void
    {
        $cartHash = 'secret_hash';
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId'])
            ->getMock();

        $this->maskedQuoteIdToQuoteIdMock->method('execute')->willReturn(1);
        $this->cartRepositoryMock->method('get')->willReturn($quoteMock);
        $this->isActiveMock->method('execute')->willReturn(true);

        $quoteMock->method('getCustomerId')->willReturn(5);

        $this->expectException(GraphQlAuthorizationException::class);
        $this->expectExceptionMessage('The current user cannot perform operations on cart "secret_hash"');

        $this->model->execute($cartHash, 9);
    }
}
