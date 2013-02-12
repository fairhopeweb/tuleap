<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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
require_once 'Binding.class.php';

class Project_Admin_UGroup_View_ShowBinding extends Project_Admin_UGroup_View_Binding {
    private $plugin_binding;

    public function __construct(UGroup $ugroup, UGroupBinding $ugroup_binding, $plugin_binding) {
        parent::__construct($ugroup, $ugroup_binding);
        $this->plugin_binding = $plugin_binding;
    }

    public function getContent() {
        $hp = Codendi_HTMLPurifier::instance();
        $content = '';
        
        $urlAdd     = '/project/admin/editugroup.php?group_id='.$this->ugroup->getProjectId().'&ugroup_id='.$this->ugroup->getId().'&func=edit&pane=binding&action=edit_binding';
        $linkAdd    = '<a href="'.$urlAdd.'">- '.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding_title').'</a><br/>';

        // LDAP plugin enabled
        $pluginManager = PluginManager::instance();
        $ldapPlugin = $pluginManager->getPluginByName('ldap');

        $ldapUserGroupManager = new LDAP_UserGroupManager($ldapPlugin->getLdap());

        $ldapGroup = $ldapUserGroupManager->getLdapGroupByGroupId($this->ugroup->getId());
        if($ldapGroup !== null) {
            $grpName = $hp->purify($ldapGroup->getCommonName());
            $title = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_list_add_upd_binding', $grpName);
        } else {
            $title = $GLOBALS['Language']->getText('plugin_ldap', 'ugroup_list_add_set_binding');
        }

        $urlDirectoryGroup    = '/project/admin/editugroup.php?group_id='.$this->ugroup->getProjectId().'&ugroup_id='.$this->ugroup->getId().'&func=edit&pane=binding&action=edit_directory_group';
        $linkDirectoryGroup    = '<br/><a href="'.$urlDirectoryGroup.'">- '. $title .'</a><br/>';

        $content .= $this->plugin_binding;

        $content .= $linkDirectoryGroup;
        $content .= $linkAdd;

        $content .= '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'binding_sources').'</h3>';
        $clones = $this->ugroup_binding->getUGroupsByBindingSource($this->ugroup->getId());
        $content .= $this->getClonesHTML($clones);
        return $content;
    }

    /**
     * Get the HTML output for ugroups bound to the current one
     *
     * @param Array $clones List of ugroups bound to this one
     *
     * @return String
     */
    private function getClonesHTML($clones) {
        $clonesHTML = '<table>';
        if (!empty($clones)) {
            $clonesHTML .= html_build_list_table_top(array($GLOBALS['Language']->getText('project_reference', 'ref_scope_P'), $GLOBALS['Language']->getText('project_ugroup_binding', 'ugroup')), false, false, false);
            $count      = 0;
            $i          = 0;
            foreach ($clones as $clone) {
                $project = $this->project_manager->getProject($clone['group_id']);
                if ($project->userIsAdmin()) {
                    $clonesHTML .= '<tr class="'. html_get_alt_row_color(++$i) .'"><td><a href="/projects/'.$project->getUnixName().'" >'.$project->getPublicName().'</a></td><td><a href="/project/admin/ugroup.php?group_id='.$project->getID().'" >'.$clone['cloneName'].'</a></td></tr>';
                } else {
                    $count ++;
                }
            }
            if ($count) {
                $clonesHTML .= '<tr class="'. html_get_alt_row_color(++$i) .'" colspan="2" ><td>and '.$count.' other ugroups you\'re not allowed to administrate</td></tr>';
            }
        } else {
            $clonesHTML .= '<tr><td>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'not_source').'</td></tr>';
        }
        $clonesHTML .= '</table>';
        return $clonesHTML;
    }
}

?>
