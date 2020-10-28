<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class FieldArtifactLinkAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FieldArtifactLinkAdapter
     */
    private $adapter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->form_element_factory = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->adapter              = new FieldArtifactLinkAdapter($this->form_element_factory);
    }

    public function testItThrowsWhenNoArtifactLinkIsFound(): void
    {
        $source_tracker = TrackerTestBuilder::aTracker()->withId(123)->build();
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')->with($source_tracker)->andReturn([]);

        $this->expectException(NoArtifactLinkFieldException::class);
        $this->adapter->build($source_tracker);
    }

    public function testItBuildArtifactLinkFieldData(): void
    {
        $source_tracker = TrackerTestBuilder::aTracker()->withId(123)->build();
        $field          = new \Tracker_FormElement_Field_ArtifactLink(
            1,
            $source_tracker->getId(),
            null,
            "links",
            "Links",
            "",
            true,
            null,
            true,
            true,
            1
        );
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')->with($source_tracker)->andReturn(
            [$field]
        );

        $artifact_link_data = new FieldData($field);

        $this->assertEquals($artifact_link_data, $this->adapter->build($source_tracker));
    }
}
