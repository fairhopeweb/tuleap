<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);


namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Tests\Builder\ProjectReferenceBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TrackerReferenceBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProgramTrackerStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ConfigurationErrorsCollectorTest extends TestCase
{
    private ConfigurationErrorsCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new ConfigurationErrorsCollector(true);
    }

    public function testItHasErrorWhenASemanticIsIncorrect(): void
    {
        $this->collector->addSemanticError(
            'Title',
            'title',
            [ProgramTrackerStub::withId(1), ProgramTrackerStub::withId(2), ProgramTrackerStub::withId(3)]
        );
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenAFieldIsRequired(): void
    {
        $this->collector->addRequiredFieldError(TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric(), 100, 'My field');
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenWorkFlowHasTransition(): void
    {
        $this->collector->addWorkflowTransitionRulesError(TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenWorkFlowHasGlobalRules(): void
    {
        $this->collector->addWorkflowTransitionDateRulesError(TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenWorkFlowHasFieldDependency(): void
    {
        $this->collector->addWorkflowDependencyError(TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenFieldIsNotSubmittable(): void
    {
        $this->collector->addSubmitFieldPermissionError(100, "My custom field", TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenFieldIsNotUpdatable(): void
    {
        $this->collector->addUpdateFieldPermissionError(100, "My custom field", TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenUserCanNotEditTeams(): void
    {
        $this->collector->userCanNotSubmitInTeam(ProgramTrackerStub::withId(200));
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenStatusIsNotSetInTeams(): void
    {
        $this->collector->addMissingSemanticInTeamErrors([1]);
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenStatusFieldIsNotSet(): void
    {
        $this->collector->addSemanticNoStatusFieldError(1);
        self::assertTrue($this->collector->hasError());
    }

    public function testItHasErrorWhenStatusHasMissingValues(): void
    {
        $this->collector->addMissingValueInSemantic(["Planned", "On going"], [1, 2]);
        self::assertTrue($this->collector->hasError());
    }

    public function testItDoesNotHaveAnyError(): void
    {
        self::assertFalse($this->collector->hasError());
    }
}
