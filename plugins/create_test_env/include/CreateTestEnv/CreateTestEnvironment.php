<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\CreateTestEnv;

use Tuleap\Password\PasswordSanityChecker;

class CreateTestEnvironment
{
    private $output_dir;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var Notifier
     */
    private $notifier;
    /**
     * @var PasswordSanityChecker
     */
    private $password_sanity_checker;

    public function __construct(Notifier $notifier, PasswordSanityChecker $password_sanity_checker, $output_dir)
    {
        $this->notifier                = $notifier;
        $this->password_sanity_checker = $password_sanity_checker;
        $this->output_dir              = $output_dir;
    }

    /**
     * @param $firstname
     * @param $lastname
     * @param $email
     *
     * @throws Exception\EmailNotUniqueException
     * @throws Exception\InvalidProjectFullNameException
     * @throws Exception\InvalidProjectUnixNameException
     * @throws Exception\InvalidRealNameException
     * @throws Exception\ProjectImportFailureException
     * @throws Exception\ProjectNotCreatedException
     * @throws Exception\UnableToCreateTemporaryDirectoryException
     * @throws Exception\UnableToWriteFileException
     * @throws Exception\InvalidPasswordException
     * @throws Exception\InvalidLoginException
     */
    public function main($firstname, $lastname, $email, $login, $password)
    {
        if (! $this->password_sanity_checker->check($password)) {
            throw new Exception\InvalidPasswordException($this->password_sanity_checker->getErrors());
        }

        $create_test_user = new CreateTestUser($firstname, $lastname, $email, $login);
        $this->serializeXmlIntoFile($create_test_user->generateXML(), 'users.xml');

        $create_test_project = new CreateTestProject($create_test_user->getUserName(), $create_test_user->getRealName());
        $this->serializeXmlIntoFile($create_test_project->generateXML(), 'project.xml');

        $this->execImport();

        $user_manager = \UserManager::instance();
        $this->user   = $user_manager->getUserByUserName($create_test_user->getUserName());
        $this->user->setPassword($password);
        $this->user->setExpiryDate(strtotime('+3 week'));
        $user_manager->updateDb($this->user);

        $this->project = \ProjectManager::instance()->getProjectByUnixName($create_test_project->getProjectUnixName());
        if (! $this->project instanceof \Project || $this->project->isError()) {
            throw new Exception\ProjectNotCreatedException();
        }

        $base_url = \HTTPRequest::instance()->getServerUrl();
        $this->notifier->notify("New project created for {$this->user->getRealName()} ({$this->user->getEmail()}): $base_url/projects/{$this->project->getUnixNameLowerCase()}. #{$this->user->getUnixName()}");
    }

    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param \SimpleXMLElement $xml
     * @param $filename
     * @throws Exception\UnableToCreateTemporaryDirectoryException
     * @throws Exception\UnableToWriteFileException
     */
    private function serializeXmlIntoFile(\SimpleXMLElement $xml, $filename)
    {
        if (!is_dir($this->output_dir) && !mkdir($this->output_dir, 0770, true) && !is_dir($this->output_dir)) {
            throw new Exception\UnableToCreateTemporaryDirectoryException(sprintf('Directory "%s" was not created', $this->output_dir));
        }
        if ($xml->saveXML($this->output_dir.DIRECTORY_SEPARATOR.$filename) !== true) {
            throw new Exception\UnableToWriteFileException("Unable to write file ".$this->output_dir.DIRECTORY_SEPARATOR.$filename);
        }
    }

    /**
     * @throws Exception\ProjectImportFailureException
     */
    private function execImport()
    {
        try {
            $cmd = sprintf('sudo -u root /usr/share/tuleap/src/utils/import_project_xml.php -u admin --automap=no-email,create:A -i %s', escapeshellarg($this->output_dir));
            $exec = new \System_Command();
            $exec->exec($cmd);
        } catch (\System_Command_CommandException $exception) {
            throw new Exception\ProjectImportFailureException($exception->getMessage(), 0, $exception);
        }
    }
}
