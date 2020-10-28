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

namespace Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Status\StatusValueMapper;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFieldsGatherer;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\TimeframeFields;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\ArtifactLinkValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\DescriptionValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\EndPeriodValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\StartDateValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\StatusValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\TitleValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementsTrackerCollection;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Changeset\Validation\ChangesetValidationContext;

final class ProjectIncrementCreatorTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectIncrementsCreator
     */
    private $mirrors_creator;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SynchronizedFieldsGatherer
     */
    private $target_fields_gatherer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|StatusValueMapper
     */
    private $status_mapper;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|TrackerArtifactCreator
     */
    private $artifact_creator;

    protected function setUp(): void
    {
        $this->transaction_executor   = new DBTransactionExecutorPassthrough();
        $this->target_fields_gatherer = M::mock(SynchronizedFieldsGatherer::class);
        $this->artifact_creator       = M::mock(TrackerArtifactCreator::class);
        $this->status_mapper          = M::mock(StatusValueMapper::class);
        $this->mirrors_creator        = new ProjectIncrementsCreator(
            $this->transaction_executor,
            $this->target_fields_gatherer,
            $this->status_mapper,
            $this->artifact_creator
        );
    }

    public function testItCreatesMirrorMilestones(): void
    {
        $copied_values              = $this->buildCopiedValues();
        $first_team_project  = new \Project(['group_id' => '102']);
        $first_tracker              = $this->buildTestTracker(8, $first_team_project);
        $second_team_project = new \Project(['group_id' => '103']);
        $second_tracker             = $this->buildTestTracker(9, $second_team_project);
        $trackers                   = new ProjectIncrementsTrackerCollection([$first_tracker, $second_tracker]);
        $current_user               = UserTestBuilder::aUser()->build();

        $this->target_fields_gatherer->shouldReceive('gather')
            ->with($first_tracker)
            ->andReturn($this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006));
        $this->target_fields_gatherer->shouldReceive('gather')
            ->with($second_tracker)
            ->andReturn($this->buildSynchronizedFields(2001, 2002, 2003, 2004, 2005, 2006));
        $this->status_mapper->shouldReceive('mapStatusValueByDuckTyping')
            ->andReturns($this->buildMappedValue(5000), $this->buildMappedValue(6000));
        $this->artifact_creator->shouldReceive('create')
            ->once()
            ->with($first_tracker, M::any(), $current_user, 123456789, false, false, M::type(ChangesetValidationContext::class))
            ->andReturn(\Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class));
        $this->artifact_creator->shouldReceive('create')
            ->once()
            ->with($second_tracker, M::any(), $current_user, 123456789, false, false, M::type(ChangesetValidationContext::class))
            ->andReturn(\Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class));

        $this->mirrors_creator->createProjectIncrements($copied_values, $trackers, $current_user);
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $copied_values         = $this->buildCopiedValues();
        $a_team_project = new \Project(['group_id' => '110']);
        $tracker               = $this->buildTestTracker(10, $a_team_project);
        $trackers              = new ProjectIncrementsTrackerCollection([$tracker]);
        $current_user          = UserTestBuilder::aUser()->build();

        $this->target_fields_gatherer->shouldReceive('gather')
            ->andReturn($this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006));
        $this->status_mapper->shouldReceive('mapStatusValueByDuckTyping')
            ->andReturn($this->buildMappedValue(5000));
        $this->artifact_creator->shouldReceive('create')->andReturnNull();

        $this->expectException(ProjectIncrementArtifactCreationException::class);
        $this->mirrors_creator->createProjectIncrements($copied_values, $trackers, $current_user);
    }

    private function buildCopiedValues(): SourceChangesetValuesCollection
    {
        $planned_value          = new \Tracker_FormElement_Field_List_Bind_StaticValue(2000, 'Planned', 'Irrelevant', 1, false);

        $title_value = new TitleValueData('Program Release');
        $description_value = new DescriptionValueData('Description', 'text');
        $status_value        = new StatusValueData([$planned_value]);
        $start_date_value    = new StartDateValueData("2020-10-01");
        $end_period_value    = new EndPeriodValueData("2020-10-31");
        $artifact_link_value = new ArtifactLinkValueData(112);

        return new SourceChangesetValuesCollection(
            112,
            $title_value,
            $description_value,
            $status_value,
            123456789,
            $start_date_value,
            $end_period_value,
            $artifact_link_value
        );
    }

    private function buildTestTracker(int $tracker_id, \Project $project): \Tracker
    {
        $tracker = new \Tracker(
            $tracker_id,
            $project->getID(),
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            \Tuleap\Tracker\TrackerColor::default(),
            false
        );
        $tracker->setProject($project);
        return $tracker;
    }

    private function buildSynchronizedFields(
        int $artifact_link_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_date_id
    ): SynchronizedFields {
        return new SynchronizedFields(
            new \Tracker_FormElement_Field_ArtifactLink($artifact_link_id, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1),
            new \Tracker_FormElement_Field_String($title_id, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2),
            new \Tracker_FormElement_Field_Text($description_id, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3),
            new \Tracker_FormElement_Field_Selectbox($status_id, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4),
            TimeframeFields::fromStartAndEndDates(
                $this->buildTestDateField($start_date_id, 89),
                $this->buildTestDateField($end_date_id, 89)
            )
        );
    }

    private function buildTestDateField(int $id, int $tracker_id): \Tracker_FormElement_Field_Date
    {
        return new \Tracker_FormElement_Field_Date($id, $tracker_id, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 1);
    }

    private function buildMappedValue(int $bind_value_id): MappedStatusValue
    {
        return new MappedStatusValue([$bind_value_id]);
    }
}