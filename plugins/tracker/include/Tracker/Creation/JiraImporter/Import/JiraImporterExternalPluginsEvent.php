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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import;

use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;

class JiraImporterExternalPluginsEvent implements Dispatchable
{
    public const NAME = 'jiraImporterExternalPluginsEvent';

    /**
     * @var SimpleXMLElement
     */
    private $xml_tracker;

    /**
     * @var PlatformConfiguration
     */
    private $jira_platform_configuration;

    /**
     * @var LoggerInterface
     * @psalm-immutable
     */
    private $logger;

    public function __construct(
        SimpleXMLElement $xml_tracker,
        PlatformConfiguration $jira_platform_configuration,
        LoggerInterface $logger
    ) {
        $this->xml_tracker                 = $xml_tracker;
        $this->logger                      = $logger;
        $this->jira_platform_configuration = $jira_platform_configuration;
    }

    public function getXmlTracker(): SimpleXMLElement
    {
        return $this->xml_tracker;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getJiraPlatformConfiguration(): PlatformConfiguration
    {
        return $this->jira_platform_configuration;
    }
}
