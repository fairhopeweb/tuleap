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

namespace Tuleap\TestManagement\REST\v1;

use PFUser;
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\LabelFieldNotFoundException;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotOpenValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;

class CampaignArtifactUpdateFieldValuesBuilder
{
    private const STATUS_CHANGE_CLOSED_VALUE = 'closed';

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var StatusValueRetriever
     */
    private $status_value_retriever;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        StatusValueRetriever $status_value_retriever
    ) {
        $this->formelement_factory    = $formelement_factory;
        $this->status_value_retriever = $status_value_retriever;
    }

    /**
     * @throws LabelFieldNotFoundException
     * @throws SemanticStatusNotDefinedException
     * @throws SemanticStatusNotOpenValueNotFoundException
     * @throws CampaignStatusChangeUnknownValueException
     *
     * @return ArtifactValuesRepresentation[]
     */
    public function getFieldValuesForCampaignArtifactUpdate(
        Tracker $tracker,
        PFUser $user,
        string $label,
        ?string $change_status
    ): array {
        $field_values = [
            $this->getLabelValueRepresentation(
                $tracker,
                $user,
                $label
            ),
            $this->getStatusValueRepresentation(
                $tracker,
                $user,
                $change_status
            )
        ];

        return array_filter($field_values);
    }

    /**
     * @throws SemanticStatusNotDefinedException
     * @throws SemanticStatusNotOpenValueNotFoundException
     * @throws CampaignStatusChangeUnknownValueException
     */
    private function getStatusValueRepresentation(
        Tracker $tracker,
        PFUser $user,
        ?string $change_status
    ): ?ArtifactValuesRepresentation {
        if ($change_status === null) {
            return null;
        }

        if ($change_status !== self::STATUS_CHANGE_CLOSED_VALUE) {
            throw new CampaignStatusChangeUnknownValueException($change_status);
        }

        $status_field = $tracker->getStatusField();
        if ($status_field === null) {
            throw new SemanticStatusNotDefinedException();
        }

        $status_value = new ArtifactValuesRepresentation();

        $first_not_open_value         = $this->status_value_retriever->getFirstNonOpenValueUserCanRead($tracker, $user);
        $status_value->bind_value_ids = [
            $first_not_open_value->getId()
        ];
        $status_value->field_id       = $status_field->getId();

        return $status_value;
    }

    /**
     * @throws LabelFieldNotFoundException
     */
    private function getLabelValueRepresentation(
        Tracker $tracker,
        PFUser $user,
        string $label
    ): ArtifactValuesRepresentation {
        $label_field = $this->formelement_factory->getUsedFieldByNameForUser(
            $tracker->getId(),
            CampaignRepresentation::FIELD_NAME,
            $user
        );

        if ($label_field === null) {
            throw new LabelFieldNotFoundException($tracker);
        }

        $label_value           = new ArtifactValuesRepresentation();
        $label_value->field_id = $label_field->getId();
        $label_value->value    = $label;

        return $label_value;
    }
}