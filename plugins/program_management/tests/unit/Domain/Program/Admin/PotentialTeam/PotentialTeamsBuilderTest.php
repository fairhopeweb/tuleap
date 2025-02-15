<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\AllProgramSearcherStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class PotentialTeamsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SearchTeamsOfProgramStub $teams_of_program_searcher;
    private UserIdentifier $user_identifier;
    private AllProgramSearcherStub $all_program_searcher;
    private ProgramForAdministrationIdentifier $program;

    protected function setUp(): void
    {
        $this->teams_of_program_searcher = SearchTeamsOfProgramStub::buildTeams(123);
        $this->all_program_searcher      = AllProgramSearcherStub::buildPrograms(126);
        $this->user_identifier           = UserIdentifierStub::buildGenericUser();
        $this->program                   = ProgramForAdministrationIdentifierBuilder::build();
    }

    public function testBuildEmptyTeamsIfNoAggregatedTeamsAndNoProjectUserIsAdminOf(): void
    {
        $this->teams_of_program_searcher = SearchTeamsOfProgramStub::buildTeams();
        self::assertEmpty(
            PotentialTeamsCollection::buildPotentialTeams(
                RetrieveProjectStub::withValidProjects(),
                $this->teams_of_program_searcher,
                $this->all_program_searcher,
                $this->program,
                $this->user_identifier
            )->getPotentialTeams()
        );
    }

    public function testBuildEmptyIfAggregatedTeamsEqualsProjectUserIsAdminOf(): void
    {
        self::assertEmpty(
            PotentialTeamsCollection::buildPotentialTeams(
                RetrieveProjectStub::withValidProjects(new \Project(['group_id' => 123])),
                $this->teams_of_program_searcher,
                $this->all_program_searcher,
                $this->program,
                $this->user_identifier
            )->getPotentialTeams()
        );
    }

    public function testBuildPotentialTeamWhenUserIsAdminOfPotentialTeamThatNotAggregatedTeamAndPotentialTeamIsNotProgram(): void
    {
        $program_project = new \Project(['group_id' => '125', 'group_name' => 'a_project']);

        $potential_teams = PotentialTeamsCollection::buildPotentialTeams(
            RetrieveProjectStub::withValidProjects(
                new \Project(['group_id' => '123', 'group_name' => 'is_team']),
                new \Project(['group_id' => '124', 'group_name' => 'potential_team']),
                $program_project,
                new \Project(['group_id' => '126', 'group_name' => 'program']),
            ),
            $this->teams_of_program_searcher,
            $this->all_program_searcher,
            ProgramForAdministrationIdentifierBuilder::buildWithId(125),
            $this->user_identifier
        );

        self::assertCount(1, $potential_teams->getPotentialTeams());
        self::assertSame(124, $potential_teams->getPotentialTeams()[0]->id);
        self::assertSame('potential_team', $potential_teams->getPotentialTeams()[0]->public_name);
    }
}
