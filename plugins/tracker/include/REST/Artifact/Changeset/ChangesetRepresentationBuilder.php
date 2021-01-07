<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\Changeset;

use Tuleap\Tracker\REST\Artifact\Changeset\Comment\CommentRepresentationBuilder;
use Tuleap\User\REST\MinimalUserRepresentation;

class ChangesetRepresentationBuilder
{
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var CommentRepresentationBuilder
     */
    private $comment_builder;

    public function __construct(
        \UserManager $user_manager,
        \Tracker_FormElementFactory $form_element_factory,
        CommentRepresentationBuilder $comment_builder
    ) {
        $this->user_manager         = $user_manager;
        $this->form_element_factory = $form_element_factory;
        $this->comment_builder      = $comment_builder;
    }

    /**
     * @throws \Tuleap\Tracker\Artifact\Changeset\Followup\InvalidCommentFormatException
     */
    public function buildWithFields(
        \Tracker_Artifact_Changeset $changeset,
        string $filter_mode,
        \PFUser $current_user
    ): ?ChangesetRepresentation {
        $last_comment = $this->getCommentOrDefaultWithNull($changeset);
        if ($filter_mode === \Tracker_Artifact_Changeset::FIELDS_COMMENTS && $last_comment->hasEmptyBody()) {
            return null;
        }
        $field_values = ($filter_mode === \Tracker_Artifact_Changeset::FIELDS_COMMENTS)
            ? []
            : $this->getRESTFieldValues($changeset, $current_user);

        return $this->buildFromFieldValues($changeset, $last_comment, $field_values);
    }

    private function getRESTFieldValues(\Tracker_Artifact_Changeset $changeset, \PFUser $user): array
    {
        $values = [];
        foreach ($this->form_element_factory->getUsedFieldsForREST($changeset->getTracker()) as $field) {
            if ($field && $field->userCanRead($user)) {
                $values[] = $field->getRESTValue($user, $changeset);
            }
        }
        return array_values(array_filter($values));
    }

    /**
     * Returns a REST representation with all fields content.
     * This does not check permissions so use it with caution.
     *
     * "A great power comes with a great responsibility"
     * @throws \Tuleap\Tracker\Artifact\Changeset\Followup\InvalidCommentFormatException
     */
    public function buildWithFieldValuesWithoutPermissions(
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $user
    ): ChangesetRepresentation {
        $last_comment = $this->getCommentOrDefaultWithNull($changeset);
        $field_values = $this->getRESTFieldValuesWithoutPermissions($changeset, $user);
        return $this->buildFromFieldValues($changeset, $last_comment, $field_values);
    }

    private function getRESTFieldValuesWithoutPermissions(\Tracker_Artifact_Changeset $changeset, \PFUser $user): array
    {
        $values = [];
        foreach ($this->form_element_factory->getUsedFieldsForREST($changeset->getTracker()) as $field) {
            $values[] = $field->getRESTValue($user, $changeset);
        }

        return array_values(array_filter($values));
    }

    /**
     * @throws \Tuleap\Tracker\Artifact\Changeset\Followup\InvalidCommentFormatException
     */
    private function buildFromFieldValues(
        \Tracker_Artifact_Changeset $changeset,
        \Tracker_Artifact_Changeset_Comment $last_comment,
        array $values
    ): ChangesetRepresentation {
        $submitted_by_id      = (int) $changeset->getSubmittedBy();
        $submitted_by_user    = $this->user_manager->getUserById($submitted_by_id);
        $submitted_by_details = ($submitted_by_user !== null)
            ? MinimalUserRepresentation::build($submitted_by_user)
            : null;

        $comment_submitted_by_id   = (int) $last_comment->getSubmittedBy();
        $comment_submitted_by_user = $this->user_manager->getUserById($comment_submitted_by_id);
        $last_modified_by          = ($comment_submitted_by_user !== null)
            ? MinimalUserRepresentation::build($comment_submitted_by_user)
            : null;

        return new ChangesetRepresentation(
            (int) $changeset->getId(),
            $submitted_by_id,
            $submitted_by_details,
            (int) $changeset->getSubmittedOn(),
            $changeset->getEmail(),
            $this->comment_builder->buildRepresentation($last_comment),
            $values,
            $last_modified_by,
            (int) $last_comment->getSubmittedOn()
        );
    }

    private function getCommentOrDefaultWithNull(\Tracker_Artifact_Changeset $changeset): \Tracker_Artifact_Changeset_Comment
    {
        return $changeset->getComment() ?: new \Tracker_Artifact_Changeset_CommentNull($changeset);
    }
}