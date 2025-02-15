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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\Workspace\RetrieveProject;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProjectManagerAdapter implements RetrieveProject
{
    public function __construct(private \ProjectManager $project_manager, private RetrieveUser $retrieve_user)
    {
    }

    public function getProjectWithId(int $project_id): \Project
    {
        return $this->project_manager->getProject($project_id);
    }

    public function getProjectsUserIsAdmin(UserIdentifier $user_identifier): array
    {
        $user = $this->retrieve_user->getUserWithId($user_identifier);
        return $this->project_manager->getProjectsUserIsAdmin($user);
    }

    public function getProjectByUnixName(string $project_name): ?\Project
    {
        return $this->project_manager->getProjectByUnixName($project_name);
    }
}
