<?php
/**
 * Copyright (c) 2026 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Space\FreeShippingRemainingCostGraphQl\Model\CalculationProvider;
use Space\FreeShippingRemainingCost\Api\Data\RemainingCostInterfaceFactory;
use Space\FreeShippingRemainingCost\Api\Data\RemainingCostInterface;
use Space\FreeShippingRemainingCost\Model\Service\InfoProvider;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;

class CalculationProviderTest extends TestCase
{
    /**
     * @var RemainingCostInterfaceFactory|MockObject
     */
    private RemainingCostInterfaceFactory|MockObject $factoryMock;

    /**
     * @var InfoProvider|MockObject
     */
    private InfoProvider|MockObject $infoProviderMock;

    /**
     * @var CalculationProvider
     */
    private CalculationProvider $model;

    protected function setUp(): void
    {
        $this->factoryMock = $this->getMockBuilder(RemainingCostInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->infoProviderMock = $this->getMockBuilder(InfoProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new CalculationProvider(
            $this->factoryMock,
            $this->infoProviderMock
        );
    }

    public function testCalculateReturnsCorrectArrayStructure(): void
    {
        $subtotal = 50.00;
        $remainingValue = 25.00;
        $message = "Spend $25.00 more for free shipping!";

        $quoteMock = $this->createMock(Quote::class);
        $addressMock = $this->createMock(Address::class);

        $quoteMock->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($addressMock);

        $addressMock->expects($this->once())
            ->method('getSubtotalWithDiscount')
            ->willReturn($subtotal);

        $remainingCostMock = $this->getMockBuilder(RemainingCostInterface::class)
            ->getMockForAbstractClass();

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->willReturn($remainingCostMock);

        $this->infoProviderMock->expects($this->once())
            ->method('getRemainingCostValue')
            ->with($quoteMock, $subtotal)
            ->willReturn($remainingValue);

        $this->infoProviderMock->expects($this->once())
            ->method('getMessage')
            ->with($remainingValue, $subtotal)
            ->willReturn($message);

        $remainingCostMock->expects($this->once())->method('setMessage')->with($message);
        $remainingCostMock->expects($this->once())->method('setValue')->with($remainingValue);
        $remainingCostMock->expects($this->once())->method('getMessage')->willReturn($message);
        $remainingCostMock->expects($this->once())->method('getValue')->willReturn($remainingValue);

        $result = $this->model->calculate($quoteMock);

        $expected = [
            RemainingCostInterface::MESSAGE => $message,
            RemainingCostInterface::VALUE => $remainingValue
        ];

        $this->assertEquals($expected, $result);
    }
}
