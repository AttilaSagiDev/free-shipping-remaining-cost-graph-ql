<?php
/**
 * Copyright (c) 2026 Attila Sagi
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace Space\FreeShippingRemainingCostGraphQl\Test\Unit\Model\Resolver;

use Magento\Framework\Api\ExtensionAttributesInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Space\FreeShippingRemainingCostGraphQl\Model\Resolver\CustomerCartRemainingCost;
use Space\FreeShippingRemainingCostGraphQl\Model\CustomerCalculation;
use Space\FreeShippingRemainingCost\Api\Data\ConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

class CustomerCartRemainingCostTest extends TestCase
{
    /**
     * @var CustomerCalculation|MockObject
     */
    private CustomerCalculation|MockObject $customerCalculationMock;

    /**
     * @var ConfigInterface|MockObject
     */
    private ConfigInterface|MockObject $configMock;

    /**
     * @var CustomerCartRemainingCost
     */
    private CustomerCartRemainingCost $resolver;

    protected function setUp(): void
    {
        $this->customerCalculationMock = $this->createMock(CustomerCalculation::class);
        $this->configMock = $this->createMock(ConfigInterface::class);

        $this->resolver = new CustomerCartRemainingCost(
            $this->customerCalculationMock,
            $this->configMock
        );
    }

    public function testResolveReturnsDataForValidCustomer(): void
    {
        $customerId = 5;
        $expectedResult = ['message' => 'Free shipping in $10', 'value' => 10.00];

        $this->configMock->method('isEnabled')->willReturn(true);

        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->getMock();

        $extensionAttributesMock = $this->getMockBuilder(ExtensionAttributesInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsCustomer'])
            ->getMock();

        $contextMock->method('getExtensionAttributes')->willReturn($extensionAttributesMock);
        $extensionAttributesMock->method('getIsCustomer')->willReturn(true);
        $contextMock->method('getUserId')->willReturn($customerId);

        $this->customerCalculationMock->expects($this->once())
            ->method('execute')
            ->with($customerId)
            ->willReturn($expectedResult);

        $result = $this->resolver->resolve(
            $this->createMock(Field::class),
            $contextMock,
            $this->createMock(ResolveInfo::class),
            null,
            null
        );

        $this->assertEquals($expectedResult, $result);
    }

    public function testResolveThrowsAuthorizationExceptionForGuest(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);

        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getExtensionAttributes'])
            ->getMock();

        $extensionAttributesMock = $this->getMockBuilder(ExtensionAttributesInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getIsCustomer'])
            ->getMock();

        $contextMock->method('getExtensionAttributes')->willReturn($extensionAttributesMock);
        $extensionAttributesMock->method('getIsCustomer')->willReturn(false);

        $this->expectException(GraphQlAuthorizationException::class);
        $this->expectExceptionMessage('The request is allowed for logged in customer');

        $this->resolver->resolve(
            $this->createMock(Field::class),
            $contextMock,
            $this->createMock(ResolveInfo::class)
        );
    }

    public function testResolveThrowsInputExceptionWhenDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Space FreeShippingRemainingCost module is not enabled.');

        $this->resolver->resolve(
            $this->createMock(Field::class),
            $this->createMock(ContextInterface::class),
            $this->createMock(ResolveInfo::class)
        );
    }
}
