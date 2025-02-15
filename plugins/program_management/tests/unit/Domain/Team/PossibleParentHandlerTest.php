<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Team;

use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeaturesStore;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProgramsOfTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;

final class PossibleParentHandlerTest extends TestCase
{
    private const FEATURE_ID   = 123;
    private const PROGRAM_ID_1 = 899;
    private const PROGRAM_ID_2 = 741;

    private FeaturesStore $feature_store;
    private PossibleParentSelectorEvent $possible_parent_selector;

    protected function setUp(): void
    {
        $this->feature_store = new class implements FeaturesStore
        {
            public int $offset     = 0;
            public int $limit      = 0;
            public int $found_rows = 0;
            /**
             * @var ProgramIdentifier[]
             */
            public array $program_identifiers = [];
            public array $open_features       = [];

            public function searchPlannableFeatures(ProgramIdentifier $program): array
            {
                return [];
            }

            public function searchOpenFeatures(int $offset, int $limit, ProgramIdentifier ...$program_identifiers): array
            {
                $this->offset              = $offset;
                $this->limit               = $limit;
                $this->program_identifiers = $program_identifiers;
                return $this->open_features;
            }

            public function searchOpenFeaturesCount(ProgramIdentifier ...$program_identifiers): int
            {
                return $this->found_rows;
            }

            public function add(int $program_id, int $artifact_id): void
            {
                $this->open_features[] = [
                    'artifact_id' => $artifact_id,
                    'program_id'  => $program_id,
                    'title'       => 'A fine feature',
                ];
            }
        };

        $this->possible_parent_selector = new class implements PossibleParentSelectorEvent {
            public int $project_id                   = 555;
            public ?array $features                  = null;
            public bool $can_create                  = true;
            public bool $tracker_is_in_root_planning = true;
            public int $offset                       = 0;
            public int $limit                        = 0;
            public int $total_size                   = 0;

            public function getUser(): UserIdentifier
            {
                return UserProxy::buildFromPFUser(UserTestBuilder::aUser()->build());
            }

            public function trackerIsInRootPlanning(): bool
            {
                return $this->tracker_is_in_root_planning;
            }

            public function getProjectId(): int
            {
                return $this->project_id;
            }

            public function disableCreate(): void
            {
                $this->can_create = false;
            }

            public function setPossibleParents(int $total_size, FeatureReference ...$features): void
            {
                $this->total_size = $total_size;
                $this->features   = $features;
            }

            public function getLimit(): int
            {
                return $this->limit;
            }

            public function getOffset(): int
            {
                return $this->offset;
            }
        };
    }

    public function testItHasOneParent(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1),
            $this->feature_store,
        );

        $this->feature_store->add(self::PROGRAM_ID_1, self::FEATURE_ID);

        $possible_parent->handle($this->possible_parent_selector);

        assertEquals([self::FEATURE_ID], array_map(static fn (FeatureReference $feature) => $feature->id, $this->possible_parent_selector->features));
        assertEquals(["A fine feature"], array_map(static fn (FeatureReference $feature) => $feature->title, $this->possible_parent_selector->features));
    }

    public function testItHasOffsetAndLimit(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1),
            $this->feature_store,
        );

        $this->possible_parent_selector->offset = 100;
        $this->possible_parent_selector->limit  = 50;
        $this->feature_store->found_rows        = 200;

        $possible_parent->handle($this->possible_parent_selector);

        assertEquals(100, $this->feature_store->offset);
        assertEquals(50, $this->feature_store->limit);
        assertEquals(200, $this->possible_parent_selector->total_size);
    }

    public function testDisableCreateWhenInTheContextOfTeamAttachedToProgramToAvoidCrossProjectRedirections(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1),
            $this->feature_store,
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertFalse($this->possible_parent_selector->can_create);
    }

    public function testItDoesntFillPossibleParentWhenTrackerIsNotInATeam(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(),
            $this->feature_store,
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertNull($this->possible_parent_selector->features);
    }

    public function testAnArtifactThatCannotBeInTeamProjectBacklogWillNotHavePossibleParents(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1),
            $this->feature_store,
        );

        $this->possible_parent_selector->tracker_is_in_root_planning = false;

        $possible_parent->handle($this->possible_parent_selector);

        assertNull($this->possible_parent_selector->features);
    }

    public function testItDoesntAddToPossibleParentsAnArtifactThatIsNotVisible(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::withNotVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1),
            $this->feature_store,
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertEquals([], $this->possible_parent_selector->features);
    }

    public function testItLooksForProgramsAtOnce(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID_1, self::PROGRAM_ID_2),
            $this->feature_store,
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertEquals([self::PROGRAM_ID_1, self::PROGRAM_ID_2], array_map(static fn (ProgramIdentifier $prgm_id) => $prgm_id->getId(), $this->feature_store->program_identifiers));
    }
}
