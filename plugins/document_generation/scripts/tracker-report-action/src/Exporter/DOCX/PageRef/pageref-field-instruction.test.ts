/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { PageRefFieldInstruction } from "./pageref-field-instruction";
import type { IContext } from "docx";

describe("PageRef Field Instructions", () => {
    it("creates a page ref field instruction with all possible options", () => {
        const pageref_field_instruction = new PageRefFieldInstruction("ref", {
            hyperlink: true,
            use_relative_position: true,
        });
        const tree = pageref_field_instruction.prepForXml({} as IContext);

        expect(tree).toStrictEqual({
            "w:instrText": [
                {
                    _attr: {
                        "xml:space": "preserve",
                    },
                },
                "PAGEREF ref \\h \\p",
            ],
        });
    });
});
