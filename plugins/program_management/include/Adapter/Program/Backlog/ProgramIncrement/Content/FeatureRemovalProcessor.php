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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\FeatureRemoval;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RemoveFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RemoveFeatureException;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

final class FeatureRemovalProcessor implements RemoveFeature
{
    /**
     * @var ProgramIncrementsDAO
     */
    private ProgramIncrementsDAO $program_increments_dao;
    private \Tracker_ArtifactFactory $artifact_factory;
    private ArtifactLinkUpdater $artifact_link_updater;
    private RetrieveUser $retrieve_user;

    public function __construct(
        ProgramIncrementsDAO $program_increments_dao,
        \Tracker_ArtifactFactory $artifact_factory,
        ArtifactLinkUpdater $artifact_link_updater,
        RetrieveUser $retrieve_user
    ) {
        $this->program_increments_dao = $program_increments_dao;
        $this->artifact_factory       = $artifact_factory;
        $this->artifact_link_updater  = $artifact_link_updater;
        $this->retrieve_user          = $retrieve_user;
    }

    public function removeFromAllProgramIncrements(FeatureRemoval $feature_removal): void
    {
        $program_ids = $this->program_increments_dao->getProgramIncrementsLinkToFeatureId($feature_removal->feature_id);
        $user        = $this->retrieve_user->getUserWithId($feature_removal->user);
        foreach ($program_ids as $program_id) {
            $program_increment_artifact = $this->artifact_factory->getArtifactById($program_id['id']);
            if (! $program_increment_artifact) {
                continue;
            }
            try {
                $this->artifact_link_updater->updateArtifactLinks(
                    $user,
                    $program_increment_artifact,
                    [],
                    [$feature_removal->feature_id],
                    \Tracker_FormElement_Field_ArtifactLink::NO_NATURE
                );
            } catch (\Tracker_NoArtifactLinkFieldException | \Tracker_Exception $e) {
                throw new RemoveFeatureException($feature_removal->feature_id, $e);
            }
        }
    }
}
