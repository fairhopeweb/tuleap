/*
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
 */

export interface GlobalExportProperties {
    readonly report_id: number;
    readonly report_name: string;
    readonly report_has_changed: boolean;
    readonly tracker_shortname: string;
    readonly platform_name: string;
    readonly platform_logo_url: string;
    readonly project_name: string;
    readonly tracker_id: number;
    readonly tracker_name: string;
    readonly user_display_name: string;
    readonly user_timezone: string;
    readonly report_url: string;
    readonly report_criteria: ReportCriteria;
}

export type ReportCriteria = ExportReportCriteria | ClassicReportCriteria;

interface ExportReportCriteria {
    readonly is_in_expert_mode: true;
    readonly query: string;
}

interface ClassicReportCriteria {
    readonly is_in_expert_mode: false;
    readonly criteria: ReadonlyArray<ReportCriterionValue>;
}

export type ReportCriterionValue = ClassicReportCriterionValue | DateReportCriterionValue;

interface ClassicReportCriterionValue {
    readonly criterion_name: string;
    readonly criterion_type: "classic";
    readonly criterion_value: string;
}

export type DateReportCriterionValue =
    | DateReportCriterionSimpleValue
    | DateReportCriterionAdvancedValue;

interface DateReportCriterionSimpleValue {
    readonly criterion_name: string;
    readonly criterion_type: "date";
    readonly criterion_from_value: null;
    readonly criterion_to_value: string;
    readonly criterion_value_operator: "=" | "<" | ">";
    readonly is_advanced: false;
}

interface DateReportCriterionAdvancedValue {
    readonly criterion_name: string;
    readonly criterion_type: "date";
    readonly criterion_from_value: string | null;
    readonly criterion_to_value: string | null;
    readonly criterion_value_operator: "=" | "<" | ">";
    readonly is_advanced: true;
}

export interface ArtifactFieldValue {
    readonly field_name: string;
    readonly field_value: string;
}

export interface ExportDocument {
    readonly name: string;
    readonly artifacts: ReadonlyArray<FormattedArtifact>;
}

export interface ArtifactContainer {
    readonly name: string;
    readonly fields: ReadonlyArray<ArtifactFieldValue>;
    readonly containers: ReadonlyArray<this>;
}

export interface FormattedArtifact {
    readonly id: number;
    readonly title: string;
    readonly fields: ReadonlyArray<ArtifactFieldValue>;
    readonly containers: ReadonlyArray<ArtifactContainer>;
}

export interface DateTimeLocaleInformation {
    readonly locale: string;
    readonly timezone: string;
}
