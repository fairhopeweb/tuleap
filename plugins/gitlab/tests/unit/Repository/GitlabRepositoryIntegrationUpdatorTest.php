<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use DateTimeImmutable;
use GitPermissionsManager;
use GitUserNotAdminException;
use PFUser;
use Project;
use Tuleap\Gitlab\REST\v1\GitlabRepositoryIntegrationPATCHRepresentation;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

final class GitlabRepositoryIntegrationUpdatorTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|GitlabRepositoryIntegrationDao
     */
    private $gitlab_repository_dao;
    /**
     * @var GitPermissionsManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $git_permission_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|GitlabRepositoryIntegrationFactory
     */
    private $gitlab_repository_factory;
    /**
     * @var GitlabRepositoryIntegrationUpdator
     */
    private $updator;

    protected function setUp(): void
    {
        $this->gitlab_repository_dao     = $this->createMock(GitlabRepositoryIntegrationDao::class);
        $this->git_permission_manager    = $this->createMock(GitPermissionsManager::class);
        $this->gitlab_repository_factory = $this->createMock(GitlabRepositoryIntegrationFactory::class);

        $this->updator = new GitlabRepositoryIntegrationUpdator(
            $this->gitlab_repository_dao,
            $this->git_permission_manager,
            $this->gitlab_repository_factory,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testItThrowsAnExceptionWhenThereIsNoGitlabRepository(): void
    {
        $integration_id = 1;

        $representation                         = new GitlabRepositoryIntegrationPATCHRepresentation();
        $representation->allow_artifact_closure = true;

        $user = new PFUser(['language_id' => 'en']);

        $this->gitlab_repository_factory->method("getIntegrationById")->with(1)->willReturn(null);

        $this->gitlab_repository_dao->expects($this->never())->method(
            "updateGitlabRepositoryIntegrationAllowArtifactClosureValue"
        );
        self::expectException(GitlabRepositoryNotIntegratedInAnyProjectException::class);

        $this->updator->updateTuleapArtifactClosureOfAGitlabIntegration($integration_id, $representation, $user);
    }

    public function testItThrowsAnExceptionIfTheUserIsNotGitAdmin(): void
    {
        $integration_id = 1;

        $representation                         = new GitlabRepositoryIntegrationPATCHRepresentation();
        $representation->allow_artifact_closure = true;

        $user = new PFUser(['language_id' => 'en']);

        $gitlab_repository = new GitlabRepositoryIntegration(
            1,
            12,
            "such gitlab ",
            "",
            "https://example.com",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->gitlab_repository_factory->method("getIntegrationById")->with(1)->willReturn($gitlab_repository);
        $this->git_permission_manager->method("userIsGitAdmin")->with(
            $user,
            $gitlab_repository->getProject()
        )->willReturn(false);

        $this->gitlab_repository_dao->expects($this->never())->method(
            "updateGitlabRepositoryIntegrationAllowArtifactClosureValue"
        );
        self::expectException(GitUserNotAdminException::class);

        $this->updator->updateTuleapArtifactClosureOfAGitlabIntegration($integration_id, $representation, $user);
    }

    public function testItUpdatesTheArtifactClosureValueInDB(): void
    {
        $integration_id = 1;

        $representation                         = new GitlabRepositoryIntegrationPATCHRepresentation();
        $representation->allow_artifact_closure = true;

        $user = new PFUser(['language_id' => 'en']);

        $gitlab_repository = new GitlabRepositoryIntegration(
            1,
            12,
            "such gitlab ",
            "",
            "https://example.com",
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->gitlab_repository_factory->method("getIntegrationById")->with(1)->willReturn($gitlab_repository);
        $this->git_permission_manager->method("userIsGitAdmin")->with(
            $user,
            $gitlab_repository->getProject()
        )->willReturn(true);

        $this->gitlab_repository_dao->expects($this->once())->method("updateGitlabRepositoryIntegrationAllowArtifactClosureValue");

        $this->updator->updateTuleapArtifactClosureOfAGitlabIntegration($integration_id, $representation, $user);
    }
}
