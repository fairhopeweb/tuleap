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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I identify a Feature, I'm its ID property
 * @psalm-immutable
 */
final class FeatureIdentifier
{
    public int $id;

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function fromId(
        VerifyIsVisibleFeature $feature_verifier,
        int $feature_id,
        UserIdentifier $user_identifier,
        ProgramIdentifier $program,
        ?PermissionBypass $bypass
    ): ?self {
        if (! $feature_verifier->isVisibleFeature($feature_id, $user_identifier, $program, $bypass)) {
            return null;
        }
        return new self($feature_id);
    }
}
