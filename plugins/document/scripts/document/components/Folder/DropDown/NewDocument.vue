<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <button
        v-if="is_item_a_folder(item) && item.user_can_write"
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
        v-on:click.prevent="showNewDocumentModal"
        data-test="document-new-item"
        data-shortcut-create-document
    >
        <i class="fa fa-fw fa-plus tlp-dropdown-menu-item-icon"></i>
        <translate>New document</translate>
    </button>
</template>

<script>
import EventBus from "../../../helpers/event-bus.js";
import { isFolder } from "../../../helpers/type-check-helper";
export default {
    name: "NewDocument",
    props: {
        item: Object,
    },
    methods: {
        showNewDocumentModal() {
            EventBus.$emit("show-new-document-modal", { detail: { parent: this.item } });
        },
        is_item_a_folder(item) {
            return isFolder(item);
        },
    },
};
</script>
