/*
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

import type { GettextProvider } from "@tuleap/gettext";
import { render, html } from "lit-html";
import { renderRichTextEditorArea, wrapTextArea } from "./lit-html-adapter";

describe(`lit-html-adapter`, () => {
    let gettext_provider: GettextProvider, doc: Document, mount_point: HTMLDivElement;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        gettext_provider = {
            gettext: (msgid): string => msgid,
        };
    });

    describe(`wrapTextArea()`, () => {
        it(`will just wrap the existing textarea in a TemplateResult so that it can be displaced`, () => {
            const textarea = doc.createElement("textarea");
            const template = wrapTextArea(textarea);
            render(template, mount_point);
            expect(mount_point.firstElementChild).toBe(textarea);
        });
    });

    describe(`renderRichTextEditorArea()`, () => {
        it(`will wrap the controls in a div with .rte_format classname, before the textarea`, () => {
            const selectbox = html`
                <select></select>
            `;
            const preview_button = html`
                <button>Preview</button>
            `;
            const help_button = html`
                <button>Help</button>
            `;
            const textarea = html`
                <textarea></textarea>
            `;
            renderRichTextEditorArea(
                {
                    mount_point,
                    selectbox,
                    preview_button,
                    helper_button: help_button,
                    textarea,
                },
                gettext_provider
            );
            expect(mount_point.innerHTML).toMatchInlineSnapshot(`
                <!---->
                <div class="rte_format">
                  Format:
                  <!---->
                  <select></select>
                  <!---->
                  <button>Preview</button>
                  <!---->
                  <button>Help</button>

                </div>

                <textarea></textarea>

                <!---->
            `);
        });
    });
});
