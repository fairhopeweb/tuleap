<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use CSRFSynchronizerToken;
use EventManager;
use ForgeConfig;
use HTTPRequest;
use Project;
use ProjectUGroup;
use TemplateRendererFactory;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\ProjectUGroup\MinimalUGroupPresenter;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Project\UserRemover;
use UGroupBinding;
use UGroupManager;
use UserHelper;

class ProjectMembersController
{
    /**
     * @var ProjectMembersDAO
     */
    private $members_dao;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    /**
     * @var UserHelper
     */
    private $user_helper;
    /**
     * @var UGroupBinding
     */

    private $user_group_bindings;
    /**
     * @var UserRemover
     */
    private $user_remover;

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        ProjectMembersDAO     $members_dao,
        CSRFSynchronizerToken $csrf_token,
        UserHelper            $user_helper,
        UGroupBinding         $user_group_bindings,
        UserRemover           $user_remover,
        EventManager          $event_manager,
        UGroupManager         $ugroup_manager
    ) {
        $this->members_dao         = $members_dao;
        $this->csrf_token          = $csrf_token;
        $this->user_helper         = $user_helper;
        $this->user_group_bindings = $user_group_bindings;
        $this->user_remover        = $user_remover;
        $this->event_manager       = $event_manager;
        $this->ugroup_manager      = $ugroup_manager;
    }

    public function display(HTTPRequest $request)
    {
        $title   = _('Members');
        $project = $request->getProject();

        $this->displayHeader($title, $project);

        $project_members_list = $this->getFormattedProjectMembers($request);
        $template_path        = ForgeConfig::get('tuleap_dir') . '/src/templates/project/members';
        $renderer             = TemplateRendererFactory::build()->getRenderer($template_path);
        $additional_modals    = new ProjectMembersAdditionalModalCollectionPresenter($project, $this->csrf_token);

        $this->event_manager->processEvent($additional_modals);

        $renderer->renderToPage(
            'project-members',
            new ProjectMembersPresenter(
                $project_members_list,
                $this->csrf_token,
                $project,
                $additional_modals
            )
        );
    }

    public function addUserToProject(HTTPRequest $request)
    {
        $this->csrf_token->check();

        $project        = $request->getProject();
        $form_unix_name = $request->get('new_project_member');

        if (! $form_unix_name) {
            return;
        }

        account_add_user_to_group($project->getID(), $form_unix_name);

        $this->user_group_bindings->reloadUgroupBindingInProject($project);
    }

    public function removeUserFromProject(HTTPRequest $request)
    {
        $this->csrf_token->check();

        $project      = $request->getProject();
        $rm_id        = $request->getValidated(
            'user_id',
            'uint',
            0
        );

        $this->user_remover->removeUserFromProject($project->getID(), $rm_id);
        $this->user_group_bindings->reloadUgroupBindingInProject($project);
    }

    private function displayHeader($title, Project $project)
    {
        $assets_path    = ForgeConfig::get('tuleap_dir') . '/src/www/assets';
        $include_assets = new IncludeAssets($assets_path, '/assets');

        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('project-admin.js'));

        $header_displayer = new HeaderNavigationDisplayer();
        $header_displayer->displayBurningParrotNavigation($title, $project, 'members');
    }

    private function getFormattedProjectMembers(HTTPRequest $request)
    {
        $project          = $request->getProject();
        $database_results = $this->members_dao->searchProjectMembers($project->getID());

        $project_members = array();

        foreach ($database_results as $member) {
            $member['ugroups']          = $this->getUGroupsPresenters($project, $member);
            $member['profile_page_url'] = "/users/" . urlencode($member['user_name']) .  "/";
            $member['is_project_admin'] = $member['admin_flags'] === UserPermissionsDao::PROJECT_ADMIN_FLAG;
            $member['username_display'] = $this->user_helper->getDisplayName(
                $member['user_name'],
                $member['realname']
            );

            $project_members[] = $member;
        }

        return $project_members;
    }

    private function getUGroupsPresenters(Project $project, array $member)
    {
        $ugroups = array();

        if ($member['admin_flags'] === UserPermissionsDao::PROJECT_ADMIN_FLAG) {
            $ugroups[] = new MinimalUGroupPresenter(
                $this->ugroup_manager->getUGroup($project, ProjectUGroup::PROJECT_ADMIN)
            );
        }

        if ($member['wiki_flags'] === UserPermissionsDao::WIKI_ADMIN_FLAG) {
            $ugroups[] = new MinimalUGroupPresenter(
                $this->ugroup_manager->getUGroup($project, ProjectUGroup::WIKI_ADMIN)
            );
        }

        if (! $member['ugroups_ids']) {
            return $ugroups;
        }

        $ugroups_ids = explode(',', $member['ugroups_ids']);
        foreach ($ugroups_ids as $ugroup_id) {
            $ugroups[] = new MinimalUGroupPresenter(
                $this->ugroup_manager->getUGroup($project, $ugroup_id)
            );
        }

        return $ugroups;
    }
}
