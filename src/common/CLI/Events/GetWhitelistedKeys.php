<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CLI\Events;

use BackendLogger;
use ForgeAccess;
use ProjectManager;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\BrowserDetection\BrowserDeprecationMessage;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Event\Dispatchable;
use Tuleap\HelpDropdown\HelpDropdownPresenterBuilder;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\layout\HomePage\NewsCollectionBuilder;
use Tuleap\layout\HomePage\StatisticsCollectionBuilder;
use Tuleap\Log\LogToGraylog2;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\System\ServiceControl;
use Tuleap\User\SwitchToPresenterBuilder;
use Tuleap\User\UserSuspensionManager;
use Tuleap\Widget\MyProjects;
use User_UserStatusManager;

final class GetWhitelistedKeys implements Dispatchable
{
    public const NAME = 'getWhitelistedKeys';

    /**
     * @var class-string[]
     */
    private array $annotated_classes = [
        ProjectManager::class,
        User_UserStatusManager::class,
        ForgeAccess::class,
        ProjectVisibilityConfigManager::class,
        Prometheus::class,
        NewsCollectionBuilder::class,
        StatisticsCollectionBuilder::class,
        DefaultProjectVisibilityRetriever::class,
        ServiceControl::class,
        UserSuspensionManager::class,
        MyProjects::class,
        BackendLogger::class,
        LogToGraylog2::class,
        InviteBuddyConfiguration::class,
        HelpDropdownPresenterBuilder::class,
        BrowserDeprecationMessage::class,
        SwitchToPresenterBuilder::class,
    ];

    /**
     * @var array<string, string>
     */
    private array $white_listed_keys = [];

    /**
     * Declare a class that holds constants with Tuleap\Config\ConfigKey or Tuleap\Config\FeatureFlagConfigKey attributes
     *
     * @param class-string $class_name
     */
    public function addConfigClass(string $class_name): void
    {
        $this->annotated_classes[] = $class_name;
    }

    /**
     * @return string[]
     */
    public function getWhiteListedKeys(): array
    {
        $this->initWhiteList();
        return array_keys($this->white_listed_keys);
    }

    public function isKeyWhiteListed(string $key): bool
    {
        $this->initWhiteList();
        return isset($this->white_listed_keys[$key]);
    }

    public function getSortedKeysWithMetadata(): \Generator
    {
        $this->initWhiteList();
        $keys = $this->white_listed_keys;
        ksort($keys, SORT_NATURAL);
        foreach ($keys as $key => $metadata) {
            yield $key => ($metadata === true ? '' : $metadata);
        }
    }

    private function initWhiteList(): void
    {
        foreach ($this->annotated_classes as $class_name) {
            $this->findTlpConfigConst($class_name);
        }
    }

    /**
     * Parse given class and extract constants that address a config key
     *
     * @param class-string $class_name
     * @throws \ReflectionException
     */
    private function findTlpConfigConst(string $class_name): void
    {
        $reflected_class = new \ReflectionClass($class_name);
        foreach ($reflected_class->getReflectionConstants() as $const) {
            foreach ($const->getAttributes() as $attribute) {
                if ($attribute->getName() === ConfigKey::class) {
                    $config_key  = $attribute->newInstance();
                    $const_value = $const->getValue();
                    if (is_string($const_value) && is_string($config_key->summary)) {
                        $this->white_listed_keys[$const_value] = $config_key->summary;
                    }
                }
                if ($attribute->getName() === FeatureFlagConfigKey::class) {
                    $config_key  = $attribute->newInstance();
                    $const_value = $const->getValue();
                    if (is_string($const_value) && is_string($config_key->summary)) {
                        $this->white_listed_keys[\ForgeConfig::FEATURE_FLAG_PREFIX . $const_value] = $config_key->summary;
                    }
                }
            }
        }
    }
}
