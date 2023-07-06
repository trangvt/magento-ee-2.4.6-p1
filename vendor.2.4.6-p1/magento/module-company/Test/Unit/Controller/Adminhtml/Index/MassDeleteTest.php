<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

class MassDeleteTest extends AbstractMass
{
    protected $actionName = 'Delete';

    public function testExecute()
    {
        if (empty($this->actionName)) {
            return;
        }

        $companysIds = [10, 11, 12];

        $this->companyCollectionMock->expects($this->any())
            ->method('getAllIds')
            ->willReturn($companysIds);

        $this->companyRepositoryMock->expects($this->any())
            ->method('deleteById')
            ->willReturnMap([[10, true], [11, true], [12, true]]);

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with(__('A total of %1 record(s) were deleted.', count($companysIds)));

        $this->resultRedirectMock->expects($this->any())
            ->method('setPath')
            ->with('company/*/index')
            ->willReturnSelf();

        $this->massAction->execute();
    }
}
