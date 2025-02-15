/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import DocumentBreadcrumb from "./DocumentBreadcrumb.vue";
import { createDocumentLocalVue } from "../../helpers/local-vue-for-test";
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { Wrapper } from "@vue/test-utils";
import type { Embedded, Folder, Item } from "../../type";

describe("DocumentBreadcrumb", () => {
    async function createWrapper(
        user_is_admin: boolean,
        current_folder_ascendant_hierarchy: Array<Folder>,
        is_loading_ascendant_hierarchy: boolean,
        current_folder: null | Folder,
        currently_previewed_item: null | Item
    ): Promise<Wrapper<DocumentBreadcrumb>> {
        return shallowMount(DocumentBreadcrumb, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            user_is_admin,
                        },
                        current_folder_ascendant_hierarchy,
                        is_loading_ascendant_hierarchy,
                        current_folder,
                        currently_previewed_item,
                    },
                }),
            },
            localVue: await createDocumentLocalVue(),
            stubs: {
                RouterLink: RouterLinkStub,
            },
        });
    }

    it(`Given user is docman administrator
        When we display the breadcrumb
        Then user should have an administration link`, async () => {
        const wrapper = await createWrapper(true, [], false, null, null);
        expect(wrapper.find("[data-test=breadcrumb-administrator-link]").exists()).toBeTruthy();
    });

    it(`Given user is regular user
        When we display the breadcrumb
        Then he should not have administrator link`, async () => {
        const wrapper = await createWrapper(false, [], false, null, null);
        expect(wrapper.find("[data-test=breadcrumb-administrator-link]").exists()).toBeFalsy();
    });

    it(`Given ascendant hierarchy has more than 5 ascendants
        When we display the breadcrumb
        Then an ellipsis is displayed so breadcrumb won't break page display`, async () => {
        const current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder" } as Folder,
            { id: 2, title: "My second folder" } as Folder,
            { id: 3, title: "My third folder" } as Folder,
            { id: 4, title: "My fourth folder" } as Folder,
            { id: 5, title: "My fifth folder" } as Folder,
            { id: 6, title: "My sixth folder" } as Folder,
            { id: 7, title: "My seventh folder" } as Folder,
        ];

        const wrapper = await createWrapper(
            false,
            current_folder_ascendant_hierarchy,
            false,
            null,
            null
        );
        expect(wrapper.find("[data-test=breadcrumb-ellipsis]").exists()).toBeTruthy();
    });

    it(`Given ascendant hierarchy has more than 5 ascendants and given we're still loading the ascendent hierarchy
        When we display the breadcrumb
        Then ellipsis is not displayed`, async () => {
        const current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder" } as Folder,
            { id: 2, title: "My second folder" } as Folder,
            { id: 3, title: "My third folder" } as Folder,
            { id: 4, title: "My fourth folder" } as Folder,
            { id: 5, title: "My fifth folder" } as Folder,
            { id: 6, title: "My sixth folder" } as Folder,
            { id: 7, title: "My seventh folder" } as Folder,
        ];

        const wrapper = await createWrapper(
            false,
            current_folder_ascendant_hierarchy,
            true,
            null,
            null
        );

        expect(wrapper.find("[data-test=breadcrumb-ellipsis]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-breadcrumb-skeleton]").exists()).toBeTruthy();
    });

    it(`Given a list of folders which are in different hierarchy level
        When we display the breadcrumb
        Then folders which are in the root folder (parent_id === 0) are removed`, async () => {
        const current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder", parent_id: 0 } as Folder,
            { id: 2, title: "My second folder", parent_id: 0 } as Folder,
            { id: 3, title: "My third folder", parent_id: 1 } as Folder,
            { id: 4, title: "My fourth folder", parent_id: 2 } as Folder,
            { id: 5, title: "My fifth folder", parent_id: 2 } as Folder,
        ];

        const wrapper = await createWrapper(
            false,
            current_folder_ascendant_hierarchy,
            false,
            null,
            null
        );

        expect(wrapper.find("[data-test=breadcrumb-ellipsis]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-breadcrumb-skeleton]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=breadcrumb-element-0]").exists()).toBeFalsy();
    });
    it(`Given a list of folders and not the current document
    When we display the breadcrumb
    Then the breadcrumb display the current folder`, async () => {
        const current_folder = { id: 1, title: "My first folder", parent_id: 0 } as Folder;
        const current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder", parent_id: 0 } as Folder,
            { id: 2, title: "My second folder", parent_id: 0 } as Folder,
            { id: 3, title: "My third folder", parent_id: 1 } as Folder,
            { id: 4, title: "My fourth folder", parent_id: 2 } as Folder,
            { id: 5, title: "My fifth folder", parent_id: 2 } as Folder,
        ];

        const wrapper = await createWrapper(
            false,
            current_folder_ascendant_hierarchy,
            false,
            current_folder,
            null
        );

        expect(wrapper.find("[data-test=breadcrumb-current-document]").exists()).toBeFalsy();
    });

    it(`Given a list of folders and the current document which is displayed
    When we display the breadcrumb
    Then the breadcrumb display the current folder`, async () => {
        const current_folder = { id: 1, title: "My first folder", parent_id: 0 } as Folder;
        const current_folder_ascendant_hierarchy = [
            { id: 1, title: "My first folder", parent_id: 0 } as Folder,
            { id: 2, title: "My second folder", parent_id: 0 } as Folder,
            { id: 3, title: "My third folder", parent_id: 1 } as Folder,
            { id: 4, title: "My fourth folder", parent_id: 2 } as Folder,
            { id: 5, title: "My fifth folder", parent_id: 2 } as Folder,
        ];
        const currently_previewed_item = {
            id: 6,
            title: "My embedded content",
            parent_id: 0,
        } as Embedded;

        const wrapper = await createWrapper(
            false,
            current_folder_ascendant_hierarchy,
            false,
            current_folder,
            currently_previewed_item
        );

        expect(wrapper.find("[data-test=breadcrumb-current-document]").exists()).toBeTruthy();
    });
});
