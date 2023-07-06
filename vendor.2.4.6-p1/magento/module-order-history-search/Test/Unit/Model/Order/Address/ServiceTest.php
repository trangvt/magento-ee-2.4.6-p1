<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Order\Address;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Order\Address\Service;
use Magento\Sales\Model\Order\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ServiceTest.
 *
 * Unit test for address service.
 */
class ServiceTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Service
     */
    private $serviceModel;

    /**
     * @var Encryptor|MockObject
     */
    private $encryptorMock;

    /**
     * @var Address|MockObject
     */
    private $addressMock;

    /**
     * @var array
     */
    private $addressData = [
        'company' => 'company',
        'firstName' => 'firstname',
        'lastName' => 'lastname',
        'street' => [
            'street1',
            'street2',
        ],
        'postCode' => '11-111',
        'city' => 'city',
        'countryId' => 'pl',
    ];

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->encryptorMock = $this
            ->getMockBuilder(Encryptor::class)
            ->disableOriginalConstructor()
            ->setMethods(['hash'])
            ->getMock();

        $this->addressMock = $this
            ->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCompany',
                    'getFirstName',
                    'getLastName',
                    'getStreet',
                    'getPostCode',
                    'getCity',
                    'getCountryId',
                ]
            )->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->serviceModel = $this->objectManagerHelper
            ->getObject(
                Service::class,
                ['encryptor' => $this->encryptorMock]
            );
    }

    /**
     * Test hashAddress() method.
     *
     * @return void
     */
    public function testHashAddress()
    {
        $this->prepareAddressExpectations();
        $this->addressMock->expects($this->once())->method('getCompany')->willReturn($this->addressData['company']);

        $this->encryptorMock->expects($this->once())->method('hash')->willReturn('123');

        $this->serviceModel->hashAddress($this->addressMock);
    }

    /**
     * Test aggregateAddress() method.
     *
     * @return void
     */
    public function testAggregateAddress()
    {
        $this->prepareAddressExpectations();

        $this->serviceModel->aggregateAddress($this->addressMock);
    }

    /**
     * @return void
     */
    private function prepareAddressExpectations()
    {
        $this->addressMock->expects($this->once())->method('getFirstname')->willReturn($this->addressData['firstName']);
        $this->addressMock->expects($this->once())->method('getLastname')->willReturn($this->addressData['lastName']);
        $this->addressMock->expects($this->once())->method('getStreet')->willReturn($this->addressData['street']);
        $this->addressMock->expects($this->once())->method('getPostcode')->willReturn($this->addressData['postCode']);
        $this->addressMock->expects($this->once())->method('getCity')->willReturn($this->addressData['city']);
        $this->addressMock->expects($this->once())->method('getCountryId')->willReturn($this->addressData['countryId']);
    }
}
