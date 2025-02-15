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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RetrieveLastChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;

final class LastChangesetRetriever implements RetrieveLastChangeset
{
    private \Tracker_ArtifactFactory $artifact_factory;
    private \Tracker_Artifact_ChangesetFactory $changeset_factory;

    public function __construct(
        \Tracker_ArtifactFactory $artifact_factory,
        \Tracker_Artifact_ChangesetFactory $changeset_factory
    ) {
        $this->artifact_factory  = $artifact_factory;
        $this->changeset_factory = $changeset_factory;
    }

    public function retrieveLastChangesetId(IterationIdentifier $iteration_identifier): ?int
    {
        $iteration_artifact = $this->artifact_factory->getArtifactById($iteration_identifier->id);
        if ($iteration_artifact === null) {
            return null;
        }
        $changeset = $this->changeset_factory->getLastChangeset($iteration_artifact);
        if ($changeset === null) {
            return null;
        }
        return (int) $changeset->getId();
    }
}
