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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureCanNotBeRankedWithItselfException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CheckFeatureIsPlannedInProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Rank\OrderFeatureRank;
use Tuleap\ProgramManagement\Domain\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Plan\InvalidFeatureIdInProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramSearcher;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanPlanInProgramIncrement;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;

final class ContentModifier implements ModifyContent
{
    private VerifyPrioritizeFeaturesPermission $permission_verifier;
    private VerifyIsProgramIncrement $program_increment_verifier;
    private ProgramSearcher $program_searcher;
    private VerifyIsVisibleFeature $visible_verifier;
    private VerifyCanBePlannedInProgramIncrement $can_be_planned_verifier;
    private OrderFeatureRank $features_rank_orderer;
    private CheckFeatureIsPlannedInProgramIncrement $check_feature_is_planned_in_PI;
    private FeaturePlanner $feature_planner;
    private VerifyUserCanPlanInProgramIncrement $can_plan_in_program_increment_verifier;
    private VerifyIsVisibleArtifact $visibility_verifier;

    public function __construct(
        VerifyPrioritizeFeaturesPermission $permission_verifier,
        VerifyIsProgramIncrement $program_increment_verifier,
        ProgramSearcher $program_searcher,
        VerifyIsVisibleFeature $visible_verifier,
        VerifyCanBePlannedInProgramIncrement $can_be_planned_verifier,
        FeaturePlanner $feature_planner,
        OrderFeatureRank $features_rank_orderer,
        CheckFeatureIsPlannedInProgramIncrement $check_feature_is_planned_in_PI,
        VerifyUserCanPlanInProgramIncrement $can_plan_in_program_increment_verifier,
        VerifyIsVisibleArtifact $visibility_verifier
    ) {
        $this->permission_verifier                    = $permission_verifier;
        $this->program_increment_verifier             = $program_increment_verifier;
        $this->program_searcher                       = $program_searcher;
        $this->visible_verifier                       = $visible_verifier;
        $this->can_be_planned_verifier                = $can_be_planned_verifier;
        $this->feature_planner                        = $feature_planner;
        $this->features_rank_orderer                  = $features_rank_orderer;
        $this->check_feature_is_planned_in_PI         = $check_feature_is_planned_in_PI;
        $this->can_plan_in_program_increment_verifier = $can_plan_in_program_increment_verifier;
        $this->visibility_verifier                    = $visibility_verifier;
    }

    public function modifyContent(int $program_increment_id, ContentChange $content_change, UserIdentifier $user): void
    {
        if ($content_change->potential_feature_id_to_add === null && $content_change->elements_to_order === null) {
            throw new AddOrOrderMustBeSetException();
        }
        $program_increment   = ProgramIncrementIdentifier::fromId(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $program_increment_id,
            $user
        );
        $program             = $this->program_searcher->getProgramOfProgramIncrement(
            $program_increment->getId(),
            $user
        );
        $user_can_prioritize = UserCanPrioritize::fromUser(
            $this->permission_verifier,
            $user,
            $program,
            null
        );

        if ($content_change->potential_feature_id_to_add !== null) {
            $this->planFeature($content_change->potential_feature_id_to_add, $program_increment, $user_can_prioritize, $program);
        }
        if ($content_change->elements_to_order !== null) {
            $this->reorderFeature($content_change->elements_to_order, $program_increment, $program, $user_can_prioritize);
        }
    }

    /**
     * @throws FeatureCannotBePlannedInProgramIncrementException
     * @throws FeatureHasPlannedUserStoryException
     * @throws AddFeatureException
     * @throws ProgramIncrementNotFoundException
     * @throws RemoveFeatureException
     * @throws FeatureNotFoundException
     */
    private function planFeature(
        int $potential_feature_id_to_add,
        ProgramIncrementIdentifier $program_increment,
        UserCanPrioritize $user,
        ProgramIdentifier $program
    ): void {
        $feature = FeatureIdentifier::fromId($this->visible_verifier, $potential_feature_id_to_add, $user, $program, null);
        if ($feature === null) {
            throw new FeatureNotFoundException($potential_feature_id_to_add);
        }
        $feature_addition = FeatureAddition::fromFeature(
            $this->can_be_planned_verifier,
            $feature,
            $program_increment,
            $user
        );
        $this->feature_planner->plan($feature_addition);
    }

    /**
     * @throws FeatureCanNotBeRankedWithItselfException
     * @throws InvalidFeatureIdInProgramIncrementException
     * @throws NotAllowedToPrioritizeException
     */
    private function reorderFeature(
        FeatureElementToOrderInvolvedInChangeRepresentation $feature_to_order_representation,
        ProgramIncrementIdentifier $program_increment,
        ProgramIdentifier $program,
        UserCanPrioritize $user
    ): void {
        $this->checkFeatureCanBeReordered($feature_to_order_representation->ids[0], $program_increment, $user);
        $this->checkFeatureCanBeReordered($feature_to_order_representation->compared_to, $program_increment, $user);
        $this->features_rank_orderer->reorder($feature_to_order_representation, (string) $program_increment->getId(), $program);
    }

    /**
     * @throws InvalidFeatureIdInProgramIncrementException
     * @throws NotAllowedToPrioritizeException
     */
    private function checkFeatureCanBeReordered(
        int $potential_feature_id_to_manipulate,
        ProgramIncrementIdentifier $program_increment,
        UserCanPrioritize $user
    ): void {
        $can_be_planned = $this->can_be_planned_verifier->canBePlannedInProgramIncrement(
            $potential_feature_id_to_manipulate,
            $program_increment->getId()
        );
        if (! $can_be_planned) {
            throw new InvalidFeatureIdInProgramIncrementException(
                $potential_feature_id_to_manipulate,
                $program_increment->getId()
            );
        }
        $feature_is_planned = $this->check_feature_is_planned_in_PI->isFeaturePlannedInProgramIncrement($program_increment->getId(), $potential_feature_id_to_manipulate);

        if (! $feature_is_planned) {
            throw new InvalidFeatureIdInProgramIncrementException(
                $potential_feature_id_to_manipulate,
                $program_increment->getId()
            );
        }

        if (! $this->can_plan_in_program_increment_verifier->userCanPlanAndPrioritize($program_increment, $user)) {
            throw new NotAllowedToPrioritizeException($user->getId(), $program_increment->getId());
        }
    }
}
