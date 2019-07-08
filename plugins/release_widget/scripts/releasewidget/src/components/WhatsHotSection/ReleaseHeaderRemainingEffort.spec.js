/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import { shallowMount } from "@vue/test-utils";
import ReleaseHeaderRemainingEffort from "./ReleaseHeaderRemainingEffort.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

let releaseData = {};
let component_options = {};

describe("ReleaseHeaderRemainingEffort", () => {
    let store_options;
    let store;

    function getPersonalWidgetInstance(store_options) {
        store = createStoreMock(store_options);

        component_options.mocks = { $store: store };

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true
        });

        return shallowMount(ReleaseHeaderRemainingEffort, component_options);
    }

    beforeEach(() => {
        store_options = {
            state: {}
        };

        releaseData = {
            label: "mile",
            id: 2,
            start_date: Date("2017-01-22T13:42:08+02:00"),
            capacity: 10
        };

        component_options = {
            propsData: {
                releaseData
            }
        };

        getPersonalWidgetInstance(store_options);
    });

    describe("Display remaining days", () => {
        it("When there is number of start days but equal at 0, Then number days of end is displayed and percent in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                number_days_until_end: 10,
                number_days_since_start: 0
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-days-tooltip]");
            const remaining_day_text = wrapper.find("[data-test=display-remaining-day-text]");
            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("0.00%");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-danger");

            expect(remaining_day_text.text()).toEqual("10");
        });

        it("When there isn't number of start days, Then 0 is displayed and a message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                }
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const remaining_day_text = wrapper.find("[data-test=display-remaining-day-text]");
            const tooltip = wrapper.find("[data-test=display-remaining-days-tooltip]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No start date defined.");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_day_text.text()).toEqual("0");
        });

        it("When there is negative number of start days, Then 0 is displayed and 0.00% in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                number_days_until_end: -10,
                number_days_since_start: -10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-days-tooltip]");
            const remaining_day_text = wrapper.find("[data-test=display-remaining-day-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("0.00%");
            expect(tooltip.classes()).not.toContain("release-remaining-value-disabled");
            expect(remaining_day_text.classes()).not.toContain("release-remaining-value-danger");
            expect(remaining_day_text.text()).toEqual("0");
        });

        it("When there is negative remaining days, Then 0 is displayed and 100% in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                number_days_until_end: -10,
                number_days_since_start: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-days-tooltip]");
            const remaining_day_text = wrapper.find("[data-test=display-remaining-day-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("100.00%");
            expect(tooltip.classes()).not.toContain("release-remaining-value-disabled");
            expect(remaining_day_text.classes()).not.toContain("release-remaining-value-danger");
            expect(remaining_day_text.text()).toEqual("0");
        });

        it("When there isn't remaining days, Then 0 is displayed and there is a message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-days-tooltip]");
            const remaining_day_text = wrapper.find("[data-test=display-remaining-day-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No start date defined.");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_day_text.text()).toEqual("0");
        });

        it("When there is remaining days but equal at 0, Then remaining days is displayed and percent in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                number_days_until_end: 0,
                number_days_since_start: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-days-tooltip]");
            const remaining_day_text = wrapper.find("[data-test=display-remaining-day-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("100.00%");
            expect(tooltip.classes()).not.toContain("release-remaining-value-danger");
            expect(remaining_day_text.text()).toEqual("0");
        });

        it("When there is remaining days and is null, Then 0 is displayed and there is a message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                number_days_since_start: 10,
                number_days_until_end: null
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-days-tooltip]");
            const remaining_day_text = wrapper.find("[data-test=display-remaining-day-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No end date defined.");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_day_text.text()).toEqual("0");
        });

        it("When there is remaining days, not null and greater than 0, Then remaining days is displayed and percent in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                number_days_until_end: 5,
                number_days_since_start: 5
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-days-tooltip]");
            const remaining_day_text = wrapper.find("[data-test=display-remaining-day-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("50.00%");
            expect(remaining_day_text.classes()).toContain("release-remaining-value-danger");
            expect(remaining_day_text.text()).toEqual("5");
        });
    });

    describe("Display remaining efforts", () => {
        it("When there is negative remaining points, Then it displays and percent in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: -1,
                initial_effort: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("110.00%");
            expect(tooltip.classes()).not.toContain("release-remaining-value-success");
            expect(remaining_point_text.classes()).not.toContain(
                "release-remaining-value-disabled"
            );
            expect(remaining_point_text.text()).toEqual("-1");
        });

        it("When there isn't remaining effort points, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                initial_effort: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No remaining effort defined.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_text.text()).toEqual("0");
        });

        it("When there is remaining effort point and is null, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: null,
                initial_effort: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No remaining effort defined.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_text.text()).toEqual("0");
        });

        it("When there is remaining effort point, not null and greater than 0, Then it's displayed and percent in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 5,
                initial_effort: 10
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("50.00%");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-success");
            expect(remaining_point_text.text()).toEqual("5");
        });

        it("When there is remaining effort point, equal at 0, Then it's displayed and percent in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 0,
                initial_effort: 5
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("100.00%");
            expect(remaining_point_text.classes()).not.toContain("release-remaining-value-success");
            expect(remaining_point_text.classes()).not.toContain(
                "release-remaining-value-disabled"
            );
            expect(remaining_point_text.text()).toEqual("0");
        });

        it("When there isn't initial effort point, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 5
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No initial effort defined.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_text.text()).toEqual("5");
        });

        it("When there is initial effort point but null, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 5
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("No initial effort defined.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_text.text()).toEqual("5");
        });

        it("When there is initial effort point but equal at 0, Then 0 is displayed and message in tooltip", () => {
            releaseData = {
                label: "mile",
                id: 2,
                planning: {
                    id: 100
                },
                start_date: null,
                remaining_effort: 5,
                initial_effort: 0
            };

            component_options.propsData = {
                releaseData
            };

            const wrapper = getPersonalWidgetInstance(store_options);

            const tooltip = wrapper.find("[data-test=display-remaining-points-tooltip]");
            const remaining_point_text = wrapper.find("[data-test=display-remaining-points-text]");

            expect(tooltip.attributes("data-tlp-tooltip")).toEqual("Initial effort equal at 0.");
            expect(remaining_point_text.classes()).toContain("release-remaining-value-disabled");
            expect(remaining_point_text.text()).toEqual("5");
        });
    });
});
