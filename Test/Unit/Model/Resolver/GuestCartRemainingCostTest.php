<?php
/**
 * Copyright (c) 2026 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Test\Unit\Model\Resolver;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Space\FreeShippingRemainingCostGraphQl\Model\Resolver\GuestCartRemainingCost;
use Space\FreeShippingRemainingCostGraphQl\Model\GuestCalculation;
use Space\FreeShippingRemainingCost\Api\Data\ConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class GuestCartRemainingCostTest extends TestCase
{
    /**
     * @var GuestCalculation|MockObject
     */
    private GuestCalculation|MockObject $guestCalculationMock;

    /**
     * @var ConfigInterface|MockObject
     */
    private ConfigInterface|MockObject $configMock;

    /**
     * @var GuestCartRemainingCost
     */
    private GuestCartRemainingCost $resolver;

    protected function setUp(): void
    {
        $this->guestCalculationMock = $this->createMock(GuestCalculation::class);
        $this->configMock = $this->createMock(ConfigInterface::class);

        $this->resolver = new GuestCartRemainingCost(
            $this->guestCalculationMock,
            $this->configMock
        );
    }

    public function testResolveReturnsDataWhenEnabledAndCartIdProvided(): void
    {
        $cartId = 'random_hash_123';
        $userId = 0;
        $args = ['cart_id' => $cartId];
        $expectedOutput = ['message' => 'Success', 'value' => 10.00];

        $this->configMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getUserId'])
            ->getMock();

        $contextMock->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $this->guestCalculationMock->expects($this->once())
            ->method('execute')
            ->with($cartId, $userId)
            ->willReturn($expectedOutput);

        $result = $this->resolver->resolve(
            $this->createMock(Field::class),
            $contextMock,
            $this->createMock(ResolveInfo::class),
            null,
            $args
        );

        $this->assertEquals($expectedOutput, $result);
    }

    public function testResolveThrowsExceptionWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Space FreeShippingRemainingCost module is not enabled.');

        $this->resolver->resolve(
            $this->createMock(Field::class),
            $this->createMock(ContextInterface::class),
            $this->createMock(ResolveInfo::class),
            null,
            ['cart_id' => '123']
        );
    }

    public function testResolveThrowsExceptionWhenCartIdIsMissing(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Required parameter "cart_id" is missing');

        $this->resolver->resolve(
            $this->createMock(Field::class),
            $this->createMock(ContextInterface::class),
            $this->createMock(ResolveInfo::class),
            null,
            []
        );
    }
}
