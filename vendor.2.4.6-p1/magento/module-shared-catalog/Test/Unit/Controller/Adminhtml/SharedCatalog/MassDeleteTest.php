<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog;

use ArrayIterator;

class MassDeleteTest extends MassTest
{
    protected $actionName = 'Delete';

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute(): void
    {
        if (empty($this->actionName)) {
            return;
        }
        $testData = [
            $this->sharedCatalog,
            $this->sharedCatalog
        ];
        $this->sharedCatalogCollectionMock
            ->method('getIterator')
            ->willReturn(new ArrayIterator($testData));

        $this->groupExcludedWebsite
            ->expects(self::exactly(2))
            ->method('delete')
            ->willReturn(true);

        $this->messageManagerMock
            ->method('addSuccess')
            ->with(__('A total of %1 record(s) were deleted.', count($testData)));

        $this->resultRedirectMock
            ->method('setPath')
            ->with('shared_catalog/*/index')
            ->willReturnSelf();

        $this->massAction->execute();
    }
}
