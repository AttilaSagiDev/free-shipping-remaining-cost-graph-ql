<?php
/**
 * Copyright (c) 2026 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Space\FreeShippingRemainingCostGraphQl\Model\CustomerCalculation;
use Space\FreeShippingRemainingCostGraphQl\Model\CalculationProvider;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

class CustomerCalculationTest extends TestCase
{
    /**
     * @var CartManagementInterface|MockObject
     */
    private CartManagementInterface|MockObject $cartManagementMock;

    /**
     * @var CalculationProvider|MockObject
     */
    private CalculationProvider|MockObject $calculationProviderMock;

    /**
     * @var CustomerCalculation
     */
    private CustomerCalculation $model;

    protected function setUp(): void
    {
        $this->cartManagementMock = $this->getMockBuilder(CartManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->calculationProviderMock = $this->getMockBuilder(CalculationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new CustomerCalculation(
            $this->cartManagementMock,
            $this->calculationProviderMock
        );
    }

    public function testExecuteReturnsCalculatedData(): void
    {
        $customerId = 1;
        $expectedResult = ['remaining_amount' => 20.00, 'show_notice' => true];

        $quoteMock = $this->createMock(Quote::class);

        $this->cartManagementMock->expects($this->once())
            ->method('getCartForCustomer')
            ->with($customerId)
            ->willReturn($quoteMock);

        $this->calculationProviderMock->expects($this->once())
            ->method('calculate')
            ->with($quoteMock)
            ->willReturn($expectedResult);

        $result = $this->model->execute($customerId);

        $this->assertEquals($expectedResult, $result);
    }

    public function testExecuteThrowsGraphQlExceptionWhenCartNotFound(): void
    {
        $customerId = 99;

        $this->cartManagementMock->expects($this->once())
            ->method('getCartForCustomer')
            ->with($customerId)
            ->willThrowException(new NoSuchEntityException(__('Exception Message')));

        $this->expectException(GraphQlNoSuchEntityException::class);
        $this->expectExceptionMessage("Could not find a cart for customer \"$customerId\".");

        $this->model->execute($customerId);
    }
}
