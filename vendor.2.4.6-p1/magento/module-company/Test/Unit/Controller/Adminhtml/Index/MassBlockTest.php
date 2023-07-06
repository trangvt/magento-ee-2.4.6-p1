<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class MassBlockTest extends AbstractMass
{
    /**
     * Action name.
     *
     * @var string
     */
    protected $actionName = 'Block';

    /**
     * Test execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $companysIds = [10, 11, 12];

        $this->companyCollectionMock->expects($this->any())
            ->method('getAllIds')
            ->willReturn($companysIds);

        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepositoryMock->expects($this->any())
            ->method('get')->willReturn($company);

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 record(s) were updated.', count($companysIds)));

        $this->resultRedirectMock->expects($this->any())
            ->method('setPath')
            ->with('company/*/index')
            ->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->massAction->execute());
    }

    /**
     * Test execute with Exception.
     */
    public function testExecuteWithException()
    {
        $exception = new \Exception('');
        $this->filterMock->expects($this->once())->method('getCollection')->willThrowException($exception);
        $this->messageManagerMock->expects($this->once())->method('addException')->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->massAction->execute());
    }

    /**
     * Test execute with LocalizedException.
     */
    public function testExecuteWithLocalizedException()
    {
        $phrase = new Phrase(__('Exception'));
        $exception = new LocalizedException($phrase);
        $this->filterMock->expects($this->once())->method('getCollection')->willThrowException($exception);
        $this->messageManagerMock->expects($this->once())->method('addError')->willReturnSelf();

        $this->assertInstanceOf(Redirect::class, $this->massAction->execute());
    }
}
