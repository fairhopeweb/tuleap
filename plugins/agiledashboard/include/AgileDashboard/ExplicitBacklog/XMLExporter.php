<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Project;
use SimpleXMLElement;

class XMLExporter
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    public function __construct(ExplicitBacklogDao $explicit_backlog_dao)
    {
        $this->explicit_backlog_dao = $explicit_backlog_dao;
    }

    public function exportExplicitBacklogConfiguration(Project $project, SimpleXMLElement $agiledashboard_node): void
    {
        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog((int) $project->getID()) === false) {
            return;
        }

        $agiledashboard_node->addChild('admin')->addChild('scrum')->addChild('explicit_backlog')->addAttribute('is_used', '1');
    }
}
