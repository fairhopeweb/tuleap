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

namespace Tuleap\Git\Reference;

use Git;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;

final class CrossReferenceGitOrganizer
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \Git_ReferenceManager
     */
    private $git_reference_manager;
    /**
     * @var CrossReferenceGitEnhancer
     */
    private $cross_reference_git_enhancer;
    /**
     * @var CommitProvider
     */
    private $commit_provider;
    /**
     * @var CommitDetailsRetriever
     */
    private $commit_details_retriever;

    public function __construct(
        \ProjectManager $project_manager,
        \Git_ReferenceManager $git_reference_manager,
        CommitProvider $commit_provider,
        CrossReferenceGitEnhancer $cross_reference_git_filler,
        CommitDetailsRetriever $commit_details_retriever
    ) {
        $this->project_manager              = $project_manager;
        $this->git_reference_manager        = $git_reference_manager;
        $this->cross_reference_git_enhancer = $cross_reference_git_filler;
        $this->commit_provider              = $commit_provider;
        $this->commit_details_retriever     = $commit_details_retriever;
    }

    public function organizeGitReferences(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        foreach ($by_nature_organizer->getCrossReferencePresenters() as $cross_reference_presenter) {
            if ($cross_reference_presenter->type !== Git::REFERENCE_NATURE) {
                continue;
            }

            $this->moveGitCrossReferenceToRepositorySection($by_nature_organizer, $cross_reference_presenter);
        }
    }

    private function moveGitCrossReferenceToRepositorySection(
        CrossReferenceByNatureOrganizer $by_nature_organizer,
        CrossReferencePresenter $cross_reference_presenter
    ): void {
        $user = $by_nature_organizer->getCurrentUser();

        $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);

        $commit_info = $this->git_reference_manager->getCommitInfoFromReferenceValue(
            $project,
            $cross_reference_presenter->target_value
        );

        $repository = $commit_info->getRepository();

        if (! $repository || ! $repository->userCanRead($user)) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

            return;
        }

        $commit = $this->commit_provider->getCommit($repository, $commit_info->getSha1());
        if (! $commit) {
            $by_nature_organizer->removeUnreadableCrossReference($cross_reference_presenter);

            return;
        }

        $commit_details = $this->commit_details_retriever->retrieveCommitDetails($repository, $commit);

        $by_nature_organizer->moveCrossReferenceToSection(
            $this->cross_reference_git_enhancer->getCrossReferencePresenterWithCommitInformation(
                $cross_reference_presenter,
                $commit_details,
                $user
            ),
            $project->getUnixNameLowerCase() . '/' . $repository->getFullName()
        );
    }
}