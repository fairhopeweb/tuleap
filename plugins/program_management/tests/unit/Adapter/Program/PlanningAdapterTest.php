<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program;

use Planning;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\PlanningHasNoMilestoneTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\SecondPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\ProgramManagementProject;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PlanningAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PlanningAdapter $adapter;
    private \PHPUnit\Framework\MockObject\Stub|\PlanningFactory $planning_factory;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->planning_factory = $this->createStub(\PlanningFactory::class);
        $this->adapter          = new PlanningAdapter($this->planning_factory, RetrieveUserStub::withGenericUser());
        $this->user_identifier  = UserIdentifierStub::buildGenericUser();
    }

    public function testThrowExceptionIfRootPlanningDoesNotExist(): void
    {
        $project_id = 101;
        $this->planning_factory->method('getRootPlanning')->willReturn(false);

        $this->expectException(TopPlanningNotFoundInProjectException::class);
        $this->adapter->getRootPlanning($this->user_identifier, $project_id);
    }

    public function testThrowExceptionIfRootPlanningHasNoPlanningTracker(): void
    {
        $project_id = 101;
        $planning   = new Planning(1, 'test', $project_id, 'backlog title', 'plan title', []);
        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $this->expectException(PlanningHasNoProgramIncrementException::class);
        $this->adapter->getRootPlanning($this->user_identifier, $project_id);
    }

    public function testItBuildARootPlanning(): void
    {
        $project_id = 101;
        $planning   = new Planning(1, 'test', $project_id, 'backlog title', 'plan title', []);
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $tracker    = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $planning->setPlanningTracker($tracker);

        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $project_id = 101;

        self::assertEquals($planning, $this->adapter->getRootPlanning($this->user_identifier, $project_id));
    }

    public function testItRetrievesTheRootMilestoneTracker(): void
    {
        $project_id = 101;
        $planning   = new Planning(1, 'test', $project_id, 'backlog title', 'plan title', []);
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $tracker    = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $planning->setPlanningTracker($tracker);

        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $wrapper_project = new ProgramManagementProject($project_id, 'team_blue', 'Team Blue', '/team_blue');
        self::assertSame(
            $tracker->getId(),
            $this->adapter->retrieveRootPlanningMilestoneTracker(
                $wrapper_project,
                UserIdentifierStub::buildGenericUser()
            )->getId()
        );
    }

    public function testItThrowErrorIfNoSecondPlanningMilestoneInProject(): void
    {
        $project_id    = 101;
        $root_planning = new Planning(1, 'test', $project_id, 'backlog title', 'plan title', []);
        $project       = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $root_tracker  = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $root_planning->setPlanningTracker($root_tracker);

        $this->planning_factory->method('getRootPlanning')->willReturn($root_planning);
        $this->planning_factory->method('getChildrenPlanning')->willReturn(null);

        $wrapper_project = new ProgramManagementProject($project_id, 'team_blue', 'Team Blue', '/team_blue');
        $this->expectException(SecondPlanningNotFoundInProjectException::class);
        $this->adapter->retrieveSecondPlanningMilestoneTracker(
            $wrapper_project,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItThrowErrorIfNoTrackerInSecondPlanningMilestoneInProject(): void
    {
        $project_id    = 101;
        $root_planning = new Planning(1, 'test', $project_id, 'backlog title', 'plan title', []);
        $project       = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $root_tracker  = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $root_planning->setPlanningTracker($root_tracker);

        $second_planning = new Planning(2, 'second', $project_id, 'backlog title', 'plan title', []);

        $this->planning_factory->method('getRootPlanning')->willReturn($root_planning);
        $this->planning_factory->method('getChildrenPlanning')->willReturn($second_planning);

        $wrapper_project = new ProgramManagementProject($project_id, 'team_blue', 'Team Blue', '/team_blue');
        $this->expectException(PlanningHasNoMilestoneTrackerException::class);
        $this->adapter->retrieveSecondPlanningMilestoneTracker(
            $wrapper_project,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItReturnSecondPlanningMilestoneTracker(): void
    {
        $project_id    = 101;
        $root_planning = new Planning(1, 'test', $project_id, 'backlog title', 'plan title', []);
        $project       = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $second_planning = new Planning(2, 'second', $project_id, 'backlog title', 'plan title', []);
        $second_tracker  = TrackerTestBuilder::aTracker()->withId(2)->withProject($project)->build();
        $second_planning->setPlanningTracker($second_tracker);
        $this->planning_factory->method('getRootPlanning')->willReturn($root_planning);
        $this->planning_factory->method('getChildrenPlanning')->willReturn($second_planning);

        $wrapper_project = new ProgramManagementProject($project_id, 'team_blue', 'Team Blue', '/team_blue');
        self::assertSame(
            $second_tracker->getId(),
            $this->adapter->retrieveSecondPlanningMilestoneTracker(
                $wrapper_project,
                UserIdentifierStub::buildGenericUser()
            )->getId()
        );
    }
}
