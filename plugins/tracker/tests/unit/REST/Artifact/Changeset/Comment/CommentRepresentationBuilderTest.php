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

namespace Tuleap\Tracker\REST\Artifact\Changeset\Comment;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Followup\InvalidCommentFormatException;

final class CommentRepresentationBuilderTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CommentRepresentationBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new CommentRepresentationBuilder();
    }

    public function testItBuildsTextCommentRepresentation(): void
    {
        $representation = $this->builder->buildRepresentation(
            $this->buildComment('A text comment', \Tracker_Artifact_Changeset_Comment::TEXT_COMMENT)
        );
        self::assertSame('text', $representation->format);
        self::assertSame('A text comment', $representation->body);
        self::assertNotEmpty($representation->post_processed_body);
    }

    public function testItBuildsHTMLCommentRepresentation(): void
    {
        $representation = $this->builder->buildRepresentation(
            $this->buildComment('<p>An HTML comment</p>', \Tracker_Artifact_Changeset_Comment::HTML_COMMENT)
        );
        self::assertSame('html', $representation->format);
        self::assertSame('<p>An HTML comment</p>', $representation->body);
        self::assertNotEmpty($representation->post_processed_body);
    }

    public function testItThrowsWhenFormatIsUnknown(): void
    {
        $this->expectException(InvalidCommentFormatException::class);
        $this->builder->buildRepresentation($this->buildComment('Irrelevant', 'invalid'));
    }

    private function buildComment(string $body, string $format): \Tracker_Artifact_Changeset_Comment
    {
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(110);
        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getArtifact')->andReturn($artifact);
        return new \Tracker_Artifact_Changeset_Comment(
            23,
            $changeset,
            null,
            null,
            101,
            1234567890,
            $body,
            $format,
            0
        );
    }
}