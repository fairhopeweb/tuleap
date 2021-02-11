/**
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

import * as tlp from "tlp";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper.js";
import {
    postRepository,
    deleteIntegrationGitlab,
    postGitlabRepository,
    patchGitlabRepository,
    GitLabRepositoryUpdate,
    getRepositoryList,
    getForkedRepositoryList,
    getGitlabRepositoryList,
    GitRepositoryRecursiveGet,
} from "./rest-querier";
import { Repository } from "../type";
import { RecursiveGetInit } from "@tuleap/tlp-fetch";

jest.mock("tlp");

describe("API querier", () => {
    describe("getRepositoryList", () => {
        it("Given a project id and a callback, then it will recursively get all project repositories and call the callback for each batch", () => {
            return new Promise<void>((done) => {
                const repositories = [{ id: 37 } as Repository, { id: 91 } as Repository];
                const repository_collection: GitRepositoryRecursiveGet = {
                    repositories,
                };
                const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet").mockImplementation(
                    <T>(
                        url: string,
                        init?: RecursiveGetInit<GitRepositoryRecursiveGet, T>
                    ): Promise<T[]> => {
                        if (!init || !init.getCollectionCallback) {
                            throw new Error();
                        }

                        return Promise.resolve(init.getCollectionCallback(repository_collection));
                    }
                );

                function displayCallback(result: Array<Repository>): void {
                    expect(result).toEqual(repositories);
                    done();
                }
                const project_id = 27;

                getRepositoryList(project_id, "push_date", displayCallback);

                expect(tlpRecursiveGet).toHaveBeenCalledWith(
                    "/api/projects/27/git",
                    expect.objectContaining({
                        params: {
                            query: '{"scope":"project"}',
                            order_by: "push_date",
                            limit: 50,
                            offset: 0,
                        },
                    })
                );
            });
        });
    });

    describe("getForkedRepositoryList", () => {
        it("Given a project id, an owner id and a callback, then it will recursively get all forks and call the callback for each batch", () => {
            return new Promise<void>((done) => {
                const repositories = [{ id: 88 } as Repository, { id: 57 } as Repository];
                const repository_collection: GitRepositoryRecursiveGet = {
                    repositories,
                };
                const tlpRecursiveGet = jest.spyOn(tlp, "recursiveGet").mockImplementation(
                    <T>(
                        url: string,
                        init?: RecursiveGetInit<GitRepositoryRecursiveGet, T>
                    ): Promise<T[]> => {
                        if (!init || !init.getCollectionCallback) {
                            throw new Error();
                        }

                        return Promise.resolve(init.getCollectionCallback(repository_collection));
                    }
                );

                function displayCallback(result: Array<Repository>): void {
                    expect(result).toEqual(repositories);
                    done();
                }

                const project_id = 5;
                const owner_id = "477";

                getForkedRepositoryList(project_id, owner_id, "push_date", displayCallback);

                expect(tlpRecursiveGet).toHaveBeenCalledWith(
                    "/api/projects/5/git",
                    expect.objectContaining({
                        params: {
                            query: '{"scope":"individual","owner_id":477}',
                            order_by: "push_date",
                            limit: 50,
                            offset: 0,
                        },
                    })
                );
            });
        });
    });

    describe("postRepository", () => {
        it("Given a project id and a repository name, then it will post the repository to create it", async () => {
            const project_id = 6;
            const repository_name = "martial/rifleshot";

            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost);

            await postRepository(project_id, repository_name);

            const stringified_body = JSON.stringify({
                project_id,
                name: repository_name,
            });

            expect(tlpPost).toHaveBeenCalledWith("/api/git/", {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: stringified_body,
            });
        });
    });

    describe("getGitlabRepositoryList", () => {
        it("Given a project id and a callback, Then it will recursively get all Gitlab repositories and call the callback for each batch", () => {
            return new Promise<void>((done) => {
                const repositories = [{ id: 37 } as Repository, { id: 91 } as Repository];

                jest.spyOn(tlp, "recursiveGet").mockImplementation(
                    <T>(
                        url: string,
                        init?: RecursiveGetInit<Array<Repository>, T>
                    ): Promise<T[]> => {
                        if (!init || !init.getCollectionCallback) {
                            throw new Error();
                        }

                        return Promise.resolve(init.getCollectionCallback(repositories));
                    }
                );

                function displayCallback(result: Array<Repository>): void {
                    expect(result).toEqual(repositories);
                    done();
                }

                const project_id = 27;

                getGitlabRepositoryList(project_id, "push_date", displayCallback);
            });
        });
    });

    describe("deleteIntegrationGitlab", () => {
        it("Given project id and repository id, Then api is queried to delete", async () => {
            const project_id = 101;
            const repository_id = 1;

            const tlpDelete = jest.spyOn(tlp, "del");
            mockFetchSuccess(tlpDelete);

            await deleteIntegrationGitlab({ project_id, repository_id });

            expect(tlpDelete).toHaveBeenCalledWith(
                "/api/v1/gitlab_repositories/" + repository_id + "?project_id=" + project_id
            );
        });
    });

    describe("postGitlabRepository", () => {
        it("Given project id and repository, token and server url, Then api is queried to create new integration", async () => {
            const project_id = 101;
            const gitlab_repository_id = 10;
            const gitlab_bot_api_token = "AzRT785";
            const gitlab_server_url = "https://example.com";

            const headers = {
                "content-type": "application/json",
            };

            const body = JSON.stringify({
                project_id,
                gitlab_repository_id,
                gitlab_server_url,
                gitlab_bot_api_token,
            });

            const tlpPost = jest.spyOn(tlp, "post");
            mockFetchSuccess(tlpPost);

            await postGitlabRepository({
                project_id,
                gitlab_repository_id,
                gitlab_server_url,
                gitlab_bot_api_token,
            });

            expect(tlpPost).toHaveBeenCalledWith("/api/gitlab_repositories", {
                headers,
                body,
            });
        });
    });

    describe("patchGitlabRepository", () => {
        it("Given body, Then api is queried to patch gitlab repository", async () => {
            const headers = {
                "content-type": "application/json",
            };

            const body = {
                update_bot_api_token: {
                    gitlab_bot_api_token: "AZERTY12345",
                    gitlab_repository_id: 20,
                    gitlab_repository_url: "https://example.com",
                },
            } as GitLabRepositoryUpdate;

            const body_stringify = JSON.stringify(body);

            const tlpPatch = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatch);

            await patchGitlabRepository(body);

            expect(tlpPatch).toHaveBeenCalledWith("/api/gitlab_repositories", {
                headers,
                body: body_stringify,
            });
        });
    });
});