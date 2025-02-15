<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);


namespace unit\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProjectFromTrackerRetriever;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Builder\TrackerReferenceBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProjectFromTrackerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\TrackerFactory
     */
    private $tracker_factory;
    private ProjectFromTrackerRetriever $retriever;
    private TrackerReference $tracker_reference;

    protected function setUp(): void
    {
        $this->tracker_factory   = $this->createStub(\TrackerFactory::class);
        $this->retriever         = new ProjectFromTrackerRetriever($this->tracker_factory);
        $this->tracker_reference = TrackerReferenceBuilder::buildWithId(1);
    }

    public function testItThrowsAnExceptionWhenTrackerIsNOtFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->retriever->fromTrackerReference($this->tracker_reference);
    }

    public function testItBuildsPrimitive(): void
    {
        $project = new \Project(['group_id' => 101, 'group_name' => "My project"]);
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);

        $project_reference = $this->retriever->fromTrackerReference($this->tracker_reference);
        self::assertEquals($project->getID(), $project_reference->getProjectId());
        self::assertEquals($project->getPublicName(), $project_reference->getProjectLabel());
    }
}
