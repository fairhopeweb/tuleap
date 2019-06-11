/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

const path = require("path");
const webpack = require("webpack");
const webpack_config = require("../webpack.config.js")[0];
const karma_configurator = require("../../../../../tools/utils/scripts/karma-configurator.js");

webpack_config.mode = "development";
webpack_config.plugins = [
    ...webpack_config.plugins,
    // Fix ngVue's stupid logger
    new webpack.DefinePlugin({ "process.env.BABEL_ENV": JSON.stringify("test") })
];

module.exports = function(config) {
    const coverage_dir = path.resolve(__dirname, "../coverage");
    const coverage_folder_name = path.basename(__dirname);
    const base_config = karma_configurator.setupBaseKarmaConfig(
        config,
        webpack_config,
        coverage_dir,
        coverage_folder_name
    );

    Object.assign(base_config, {
        files: [karma_configurator.jasmine_promise_matchers_path, "src/index.spec.js"],
        preprocessors: {
            "src/index.spec.js": ["webpack"]
        }
    });

    config.set(base_config);
};
