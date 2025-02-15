<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanPlanInProgramIncrement;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class ProgramIncrementsRetriever implements RetrieveProgramIncrements
{
    private ProgramIncrementsDAO $program_increments_dao;
    private \Tracker_ArtifactFactory $artifact_factory;
    private SemanticTimeframeBuilder $semantic_timeframe_builder;
    private LoggerInterface $logger;
    private RetrieveUser $user_manager_adapter;
    private VerifyUserCanPlanInProgramIncrement $can_plan_in_program_increment_verifier;
    private VerifyIsProgramIncrement $program_increment_verifier;
    private VerifyIsVisibleArtifact $visibility_verifier;

    public function __construct(
        ProgramIncrementsDAO $program_increments_dao,
        \Tracker_ArtifactFactory $artifact_factory,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        LoggerInterface $logger,
        RetrieveUser $user_manager_adapter,
        VerifyUserCanPlanInProgramIncrement $can_plan_in_program_increment_verifier,
        VerifyIsProgramIncrement $program_increment_verifier,
        VerifyIsVisibleArtifact $visibility_verifier
    ) {
        $this->program_increments_dao                 = $program_increments_dao;
        $this->artifact_factory                       = $artifact_factory;
        $this->semantic_timeframe_builder             = $semantic_timeframe_builder;
        $this->logger                                 = $logger;
        $this->user_manager_adapter                   = $user_manager_adapter;
        $this->can_plan_in_program_increment_verifier = $can_plan_in_program_increment_verifier;
        $this->program_increment_verifier             = $program_increment_verifier;
        $this->visibility_verifier                    = $visibility_verifier;
    }

    /**
     * @return ProgramIncrement[]
     */
    public function retrieveOpenProgramIncrements(ProgramIdentifier $program, UserIdentifier $user_identifier): array
    {
        $user                        = $this->user_manager_adapter->getUserWithId($user_identifier);
        $program_increment_rows      = $this->program_increments_dao->searchOpenProgramIncrements($program->getId());
        $program_increment_artifacts = [];

        foreach ($program_increment_rows as $program_increment_row) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $program_increment_row['id']);
            if ($artifact !== null) {
                $program_increment_artifacts[] = $artifact;
            }
        }

        $program_increments = [];
        foreach ($program_increment_artifacts as $program_increment_artifact) {
            $program_increment = $this->getProgramIncrementFromArtifact($user, $program_increment_artifact);
            if ($program_increment !== null) {
                $program_increments[] = $program_increment;
            }
        }

        $this->sortProgramIncrementByStartDate($program_increments);

        return $program_increments;
    }

    private function getProgramIncrementFromArtifact(
        \PFUser $user,
        Artifact $program_increment_artifact
    ): ?ProgramIncrement {
        $user_identifier = UserProxy::buildFromPFUser($user);
        $title           = $program_increment_artifact->getTitle();
        if ($title === null) {
            return null;
        }

        $status       = null;
        $status_field = $program_increment_artifact->getTracker()->getStatusField();
        if ($status_field !== null && $status_field->userCanRead($user)) {
            $status = $program_increment_artifact->getStatus();
        }

        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($program_increment_artifact->getTracker());
        $time_period        = $semantic_timeframe->getTimeframeCalculator(
        )->buildTimePeriodWithoutWeekendForArtifactForREST(
            $program_increment_artifact,
            $user,
            $this->logger
        );

        $user_can_plan = $this->can_plan_in_program_increment_verifier->userCanPlan(
            ProgramIncrementIdentifier::fromId(
                $this->program_increment_verifier,
                $this->visibility_verifier,
                $program_increment_artifact->getId(),
                $user_identifier
            ),
            $user_identifier
        );

        return new ProgramIncrement(
            $program_increment_artifact->getId(),
            $title,
            $program_increment_artifact->getUri(),
            $program_increment_artifact->getXRef(),
            $program_increment_artifact->userCanUpdate($user),
            $user_can_plan,
            $status,
            $time_period->getStartDate(),
            $time_period->getEndDate()
        );
    }

    /**
     * @param ProgramIncrement[] $program_increments
     */
    private function sortProgramIncrementByStartDate(array &$program_increments): void
    {
        usort($program_increments, function (ProgramIncrement $a, ProgramIncrement $b) {
            if ($a->start_date === $b->start_date) {
                return 0;
            }
            if ($a->start_date === null) {
                return -1;
            }
            if ($b->start_date === null) {
                return 1;
            }

            return $a->start_date > $b->start_date ? -1 : 1;
        });
    }
}
