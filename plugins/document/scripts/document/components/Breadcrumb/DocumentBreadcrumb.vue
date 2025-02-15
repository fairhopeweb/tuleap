<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <div class="breadcrumb-container document-breadcrumb">
        <breadcrumb-privacy
            v-bind:project_flags="project_flags"
            v-bind:privacy="privacy"
            v-bind:project_public_name="project_public_name"
        />
        <nav class="breadcrumb">
            <div class="breadcrumb-item breadcrumb-project">
                <a v-bind:href="project_url" class="breadcrumb-link">
                    {{ project_public_name }}
                </a>
            </div>
            <div v-bind:class="getBreadcrumbClass()">
                <router-link
                    v-bind:to="{ name: 'root_folder' }"
                    class="breadcrumb-link"
                    v-bind:title="`${$gettext('Project documentation')}`"
                >
                    <i class="breadcrumb-link-icon far fa-folderpen"></i>
                    <translate>Documents</translate>
                </router-link>
                <div class="breadcrumb-switch-menu-container">
                    <nav class="breadcrumb-switch-menu" v-if="isAdmin()">
                        <span class="breadcrumb-dropdown-item">
                            <a
                                class="breadcrumb-dropdown-link"
                                v-bind:href="documentAdministrationUrl()"
                                v-bind:title="`${$gettext('Administration')}`"
                                data-test="breadcrumb-administrator-link"
                            >
                                <i class="fa fa-cog fa-fw"></i>
                                <translate>Administration</translate>
                            </a>
                        </span>
                    </nav>
                </div>
            </div>

            <span
                class="breadcrumb-item breadcrumb-item-disabled"
                v-if="isEllipsisDisplayed()"
                data-test="breadcrumb-ellipsis"
            >
                <span
                    class="breadcrumb-link"
                    v-bind:title="`${$gettext(
                        'Parent folders are not displayed to not clutter the interface'
                    )}`"
                >
                    ...
                </span>
            </span>
            <document-breadcrumb-element
                v-for="parent in currentFolderAscendantHierarchyToDisplay()"
                v-bind:key="parent.id"
                v-bind:item="parent"
                v-bind:data-test="`breadcrumb-element-${parent.id}`"
            />
            <span
                class="breadcrumb-item"
                v-if="is_loading_ascendant_hierarchy"
                data-test="document-breadcrumb-skeleton"
            >
                <a class="breadcrumb-link" href="#">
                    <span class="tlp-skeleton-text"></span>
                </a>
            </span>
            <document-breadcrumb-document
                v-if="isCurrentDocumentDisplayed()"
                v-bind:current_document="currently_previewed_item"
                v-bind:parent_folder="current_folder"
                data-test="breadcrumb-current-document"
            />
        </nav>
    </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import type { Folder, Item } from "../../type";
import DocumentBreadcrumbElement from "./DocumentBreadcrumbElement.vue";
import DocumentBreadcrumbDocument from "./DocumentBreadcrumbDocument.vue";
import { BreadcrumbPrivacy } from "@tuleap/vue-breadcrumb-privacy";
import type { ProjectFlag, ProjectPrivacy } from "@tuleap/vue-breadcrumb-privacy";

const configuration = namespace("configuration");

@Component({
    components: { DocumentBreadcrumbElement, DocumentBreadcrumbDocument, BreadcrumbPrivacy },
})
export default class DocumentBreadcrumb extends Vue {
    @State
    readonly current_folder_ascendant_hierarchy!: Array<Folder>;

    @State
    readonly is_loading_ascendant_hierarchy!: boolean;

    @State
    readonly currently_previewed_item!: Item;

    @State
    readonly current_folder!: Folder;

    @configuration.State
    readonly project_url!: string;

    @configuration.State
    readonly privacy!: ProjectPrivacy;

    @configuration.State
    readonly project_flags!: Array<ProjectFlag>;

    @configuration.State
    readonly project_id!: number;

    @configuration.State
    readonly project_public_name!: string;

    @configuration.State
    readonly user_is_admin!: boolean;

    private max_nb_to_display = 5;

    documentAdministrationUrl(): string {
        return "/plugins/docman/?group_id=" + this.project_id + "&action=admin";
    }
    isAdmin(): boolean {
        return this.user_is_admin;
    }
    getBreadcrumbClass(): string {
        if (this.isAdmin()) {
            return "breadcrumb-switchable breadcrumb-item";
        }

        return "breadcrumb-item";
    }
    isEllipsisDisplayed(): boolean {
        if (this.is_loading_ascendant_hierarchy) {
            return false;
        }

        return this.current_folder_ascendant_hierarchy.length > this.max_nb_to_display;
    }
    currentFolderAscendantHierarchyToDisplay(): Array<Item> {
        return this.current_folder_ascendant_hierarchy
            .filter((parent) => parent.parent_id !== 0)
            .slice(-this.max_nb_to_display);
    }
    isCurrentDocumentDisplayed(): boolean {
        return this.currently_previewed_item !== null && this.current_folder !== null;
    }
}
</script>
