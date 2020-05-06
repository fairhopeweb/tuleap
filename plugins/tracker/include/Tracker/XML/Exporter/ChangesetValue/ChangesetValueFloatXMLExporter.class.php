<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter extends Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter
{
    /**
     * @var FieldChangeFloatBuilder
     */
    private $field_change_float_builder;

    public function __construct(FieldChangeFloatBuilder $field_change_float_builder)
    {
        $this->field_change_float_builder = $field_change_float_builder;
    }

    protected function getFieldChangeType()
    {
        return Tracker_FormElementFactory::FIELD_FLOAT_TYPE;
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        assert($changeset_value instanceof Tracker_Artifact_ChangesetValue_Float);

        $this->field_change_float_builder->build(
            $changeset_xml,
            $changeset_value->getField()->getName(),
            $changeset_value->getValue()
        );
    }
}
