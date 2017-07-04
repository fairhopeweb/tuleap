<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\SVN\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\Svn\Repository\HookConfig;

class CommitRulesRepresentation extends \Luracast\Restler\Data\ValueObject
{
    /**
     * @var Boolean {@type boolean}
     */
    public $mandatory_reference;

    /**
     * @var Boolean {@type boolean}
     */
    public $allow_commit_message_change;

    public function build(HookConfig $hook_config)
    {
        $this->mandatory_reference = JsonCast::toBoolean(
            $hook_config->getHookConfig(HookConfig::MANDATORY_REFERENCE)
        );

        $this->allow_commit_message_change = JsonCast::toBoolean(
            $hook_config->getHookConfig(HookConfig::COMMIT_MESSAGE_CAN_CHANGE)
        );
    }

    public function toArray()
    {
        return array(
            HookConfig::MANDATORY_REFERENCE       => $this->mandatory_reference,
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => $this->allow_commit_message_change,
        );
    }
}
