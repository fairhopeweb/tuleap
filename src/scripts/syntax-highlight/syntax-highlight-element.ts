/*
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

export class SyntaxHighlightElement extends HTMLElement {
    public connectedCallback(): void {
        const code_block = this.querySelector("code");
        if (!code_block) {
            return;
        }

        const observer = new IntersectionObserver(async (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    // This is needed to force Prism to not highlight everything when its module is loaded
                    window.Prism = window.Prism || { manual: true };
                    const { syntaxHighlightElement } = await import(
                        /* webpackChunkName: "prism-syntax-hl" */ "./prism"
                    );
                    syntaxHighlightElement(code_block);
                    observer.unobserve(this);
                }
            }
        });

        observer.observe(this);
    }
}
