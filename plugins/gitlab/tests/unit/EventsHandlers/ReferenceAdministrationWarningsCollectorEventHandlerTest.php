<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\EventsHandlers;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Gitlab\Reference\GitlabCommitReference;
use Tuleap\Project\Admin\Reference\ReferenceAdministrationWarningsCollectorEvent;

class ReferenceAdministrationWarningsCollectorEventHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ReferenceAdministrationWarningsCollectorEventHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new ReferenceAdministrationWarningsCollectorEventHandler();
    }

    public function testItSendsAWarningWhenTheReferenceIsUsedInProjectReferences(): void
    {
        $event = new ReferenceAdministrationWarningsCollectorEvent([
            $this->buildReferenceWithKeyword('activity'),
            $this->buildReferenceWithKeyword('bugs'),
            $this->buildReferenceWithKeyword(GitlabCommitReference::REFERENCE_NAME),
            $this->buildReferenceWithKeyword('stuff'),
        ]);


        $this->handler->handle($event);
        $this->assertContains(
            "The project reference based on the keyword 'gitlab_commit' is overriding the system reference used by the GitLab plugin.",
            $event->getWarningMessages()
        );
    }

    public function testItDoesNotSendAnyWarningOtherwise(): void
    {
        $event = new ReferenceAdministrationWarningsCollectorEvent([
            $this->buildReferenceWithKeyword('activity'),
            $this->buildReferenceWithKeyword('bugs'),
            $this->buildReferenceWithKeyword('spike'),
            $this->buildReferenceWithKeyword('stuff'),
        ]);

        $this->handler->handle($event);
        $this->assertEquals([], $event->getWarningMessages());
    }

    private function buildReferenceWithKeyword(string $keyword): \Reference
    {
        return new \Reference(
            0,
            $keyword,
            'desc',
            'link',
            'P',
            'service_short_name',
            'nature',
            1,
            101
        );
    }
}