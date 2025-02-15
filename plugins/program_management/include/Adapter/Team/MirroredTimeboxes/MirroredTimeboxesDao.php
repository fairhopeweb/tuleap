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

namespace Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveTimeboxFromMirroredTimebox;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;

final class MirroredTimeboxesDao extends DataAccessObject implements SearchMirroredTimeboxes, RetrieveTimeboxFromMirroredTimebox
{
    public function searchMirroredTimeboxes(int $timebox_id): array
    {
        $sql = "SELECT parent_art.id AS id
                FROM tracker_artifact parent_art
                         INNER JOIN tracker                                 AS parent_tracker ON (parent_tracker.id = parent_art.tracker_id AND parent_tracker.deletion_date IS NULL)
                         INNER JOIN tracker_field                           AS f              ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                         INNER JOIN tracker_changeset_value                 AS cv             ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                         INNER JOIN tracker_changeset_value_artifactlink    AS artlink        ON (artlink.changeset_value_id = cv.id)
                         INNER JOIN tracker_artifact                        AS linked_art     ON (linked_art.id = artlink.artifact_id)
                         INNER JOIN tracker                                 AS linked_tracker ON (linked_art.tracker_id = linked_tracker.id AND linked_tracker.deletion_date IS NULL)
                         INNER JOIN plugin_program_management_team_projects AS team           ON (parent_tracker.group_id = team.team_project_id AND linked_tracker.group_id = team.program_project_id)
                WHERE linked_art.id  = ?
                  AND IFNULL(artlink.nature, '') = ?";

        $rows = $this->getDB()->first($sql, $timebox_id, TimeboxArtifactLinkType::ART_LINK_SHORT_NAME);

        return array_map(static fn(int $mirrored_timebox_id) => new MirroredTimeboxProxy($mirrored_timebox_id), $rows);
    }

    public function getTimeboxFromMirroredTimeboxId(int $mirrored_timebox_id): ?int
    {
        $sql = "SELECT linked_art.id
                FROM tracker_artifact parent_art
                         INNER JOIN tracker_field                           AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                         INNER JOIN tracker_changeset_value                 AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                         INNER JOIN tracker_changeset_value_artifactlink    AS artlink    ON (artlink.changeset_value_id = cv.id)
                         INNER JOIN tracker_artifact                        AS linked_art ON (linked_art.id = artlink.artifact_id)
                         INNER JOIN tracker                                 AS t          ON (t.id = parent_art.tracker_id)
                         INNER JOIN plugin_program_management_team_projects AS team       ON t.group_id = team.team_project_id
                WHERE parent_art.id  = ?
                  AND t.deletion_date IS NULL
                  AND IFNULL(artlink.nature, '') = ?;";

        $timebox_id = $this->getDB()->cell($sql, $mirrored_timebox_id, TimeboxArtifactLinkType::ART_LINK_SHORT_NAME);
        if (! $timebox_id) {
            return null;
        }

        return $timebox_id;
    }
}
