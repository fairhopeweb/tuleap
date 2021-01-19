/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import hotkeys from "hotkeys-js";
import { EVENT_TLP_MODAL_SHOWN, EVENT_TLP_MODAL_HIDDEN } from "../../themes/tlp/src/js/modal";

export const HOTKEYS_SCOPE_NO_MODAL = "scope-no-modal";
const HOTKEYS_SCOPE_MODAL_SHOWN = "scope-modal-shown";

hotkeys.setScope(HOTKEYS_SCOPE_NO_MODAL);

document.addEventListener(EVENT_TLP_MODAL_SHOWN, () => {
    hotkeys.setScope(HOTKEYS_SCOPE_MODAL_SHOWN);
});

document.addEventListener(EVENT_TLP_MODAL_HIDDEN, () => {
    hotkeys.setScope(HOTKEYS_SCOPE_NO_MODAL);
});