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

namespace Tuleap\Tracker\Test\Builders;

use Tuleap\Tracker\Artifact\Artifact;

final class ChangesetTestBuilder
{
    private Artifact $artifact;
    private int $submitted_by_id        = 101;
    private int $submission_timestamp   = 1234567890;
    private ?string $submitted_by_email = 'anonymous_user@example.com';

    private function __construct(private string $id)
    {
        $artifact_id    = 171;
        $this->artifact = ArtifactTestBuilder::anArtifact($artifact_id)->build();
    }

    public static function aChangeset(string $changeset_id): self
    {
        return new self($changeset_id);
    }

    public function ofArtifact(Artifact $artifact): self
    {
        $this->artifact = $artifact;
        return $this;
    }

    public function submittedBy(int $user_id): self
    {
        $this->submitted_by_id = $user_id;
        return $this;
    }

    /**
     * @psalm-pure
     */
    public function build(): \Tracker_Artifact_Changeset
    {
        return new \Tracker_Artifact_Changeset(
            $this->id,
            $this->artifact,
            $this->submitted_by_id,
            $this->submission_timestamp,
            $this->submitted_by_email
        );
    }
}
