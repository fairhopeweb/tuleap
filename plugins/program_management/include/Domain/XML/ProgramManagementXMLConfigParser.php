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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\XML;

use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotLoadXMLConfigFileException;

final class ProgramManagementXMLConfigParser implements ParseXMLConfig
{
    private const PROGRAM_MANAGEMENT_CONFIG_XML = 'program-management-config.xml';

    public function isThereAConfigToImport(string $extraction_path): bool
    {
        $xml_path = $extraction_path . '/' . self::PROGRAM_MANAGEMENT_CONFIG_XML;

        return file_exists($xml_path);
    }

    /**
     * @throws \XML_ParseException
     * @throws CannotLoadXMLConfigFileException
     */
    public function parseConfig(string $extraction_path): \SimpleXMLElement
    {
        $xml_path   = $extraction_path . '/' . self::PROGRAM_MANAGEMENT_CONFIG_XML;
        $xml_config = simplexml_load_string(file_get_contents($xml_path));
        if (! $xml_config) {
            throw new CannotLoadXMLConfigFileException($xml_path);
        }

        $xml_validator = new \XML_RNGValidator();
        $rng_path      = dirname(__DIR__, 3) . '/resources/program_management.rng';

        $xml_validator->validate($xml_config, $rng_path);

        return $xml_config;
    }
}
