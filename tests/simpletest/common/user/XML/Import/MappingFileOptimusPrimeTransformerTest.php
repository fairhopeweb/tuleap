<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
namespace User\XML\Import;

use TuleapTestCase;
use PFUser;
use UserManager;

class MappingFileOptimusPrimeTransformer_BaseTest extends TuleapTestCase {

    /** @var MappingFileOptimusPrimeTransformer */
    protected $transformer;

    /** @var UsersToBeImportedCollection */
    protected $collection;

    protected $filename;

    public function setUp() {
        parent::setUp();
        $this->filename = __DIR__ .'/_fixtures/users.csv';

        $this->user_manager = mock('UserManager');

        $cstevens         = aUser()->withUserName('cstevens')->build();
        $to_be_activated  = aUser()->withUserName('to.be.activated')->build();
        $already_existing = aUser()->withUserName('already.existing')->build();

        stub($this->user_manager)->getUserByUserName('cstevens')->returns($cstevens);
        stub($this->user_manager)->getUserByUserName('to.be.activated')->returns($to_be_activated);
        stub($this->user_manager)->getUserByUserName('already.existing')->returns($already_existing);

        $this->transformer = new MappingFileOptimusPrimeTransformer($this->user_manager);
        $this->collection  = new UsersToBeImportedCollection();
    }

    protected function addAlreadyExistingUserToCollection() {
        $this->collection->add(
            new AlreadyExistingUser(
                aUser()->withUserName('already.existing')->build()
            )
        );
    }

    protected function addToBeActivatedUserToCollection() {
        $this->collection->add(
            new ToBeActivatedUser(
                aUser()->withUserName('to.be.activated')->build()
            )
        );
    }

    protected function addToBeCreatedUserToCollection() {
        $this->collection->add(
            new ToBeCreatedUser(
                'to.be.created',
                'To Be Created',
                'to.be.created@example.com'
            )
        );
    }

    protected function addEmailDoesNotMatchUserToCollection() {
        $this->collection->add(
            new EmailDoesNotMatchUser(
                aUser()->withUserName('email.does.not.match')->build(),
                'email.does.not.match@example.com'
            )
        );
    }

    protected function addToBeMappedUserToCollection() {
        $this->collection->add(
            new ToBeMappedUser(
                'to.be.mapped',
                'To Be Mapped',
                array(
                    aUser()->withUserName('cstevens')->build()
                )
            )
        );
    }

    public function tearDown() {
        if (is_file($this->filename)) {
            unlink($this->filename);
        }
        parent::tearDown();
    }

    protected function generateCSV($name, $action) {
        $content = <<<EOS
name,action,comments
$name,$action,"Osef joseph"

EOS;
        file_put_contents($this->filename, $content);
    }

    public function appendToCSV($name, $action) {
        $content = <<<EOS
$name,$action,"Osef joseph"

EOS;
        file_put_contents($this->filename, $content, FILE_APPEND);
    }
}

class MappingFileOptimusPrimeTransformer_transformTest extends MappingFileOptimusPrimeTransformer_BaseTest {

    public function itTransformsAToBeMappedToAWillBeMappedUser() {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $cstevens2 = aUser()->withUserName('cstevens2')->build();

        $this->collection->add(
            new ToBeMappedUser(
                'to.be.mapped',
                'To Be Mapped',
                array($cstevens, $cstevens2)
            )
        );
        $this->generateCSV('to.be.mapped', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);

        $user = $new_collection->getUser('to.be.mapped');
        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAnEmailDoesnotMatchToAWillBeMappedUser() {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $this->addEmailDoesNotMatchUserToCollection();
        $this->generateCSV('email.does.not.match', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUser('email.does.not.match');

        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAToBeCreatedToAWillBeMappedUser() {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUser('to.be.created');

        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAToBeActivatedToAWillBeMappedUser() {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $this->addToBeActivatedUserToCollection();
        $this->generateCSV('to.be.activated', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUser('to.be.activated');

        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAnAlreadyExistingToAWillBeMappedUser() {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $this->addAlreadyExistingUserToCollection();
        $this->generateCSV('already.existing', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUser('already.existing');

        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAToBeCreatedToAWillBeCreatedUser() {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUser('to.be.created');

        $this->assertIsA($user, 'User\XML\Import\WillBeCreatedUser');
        $this->assertEqual($user->getUserName(), 'to.be.created');
        $this->assertEqual($user->getRealName(), 'To Be Created');
        $this->assertEqual($user->getEmail(), 'to.be.created@example.com');
    }

    public function itTransformsAToBeActivatedToAWillBeActivatedUser() {
        $to_be_activated = $this->user_manager->getUserByUserName('to.be.activated');
        $this->addToBeActivatedUserToCollection();
        $this->generateCSV('to.be.activated', 'activate');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUser('to.be.activated');

        $this->assertIsA($user, 'User\XML\Import\WillBeActivatedUser');
        $this->assertEqual($user->getUser(), $to_be_activated);
        $this->assertEqual($user->getUserName(), 'to.be.activated');
    }

    public function itTransformsAnAlreadyExistingToAWillBeActivatedUser() {
        $already_existing = $this->user_manager->getUserByUserName('already.existing');
        $this->addAlreadyExistingUserToCollection();
        $this->generateCSV('already.existing', 'activate');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUser('already.existing');

        $this->assertIsA($user, 'User\XML\Import\WillBeActivatedUser');
        $this->assertEqual($user->getUser(), $already_existing);
        $this->assertEqual($user->getUserName(), 'already.existing');
    }

    public function itThrowsAnExceptionWhenAUserInCollectionIsNotTransformedOrKept() {
        $this->addToBeActivatedUserToCollection();
        $this->addToBeMappedUserToCollection();
        $this->generateCSV('to.be.activated', 'activate');

        $this->expectException('User\XML\Import\MissingEntryInMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsAnExceptionIfUsernameAppearsMultipleTimesInCSVFile() {
        $this->addToBeMappedUserToCollection();
        $this->generateCSV('to.be.mapped', 'map:cstevens');
        $this->appendToCSV('to.be.mapped', 'map:already.existing');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itSkipsAlreadyExistingUsersNotFoundInMapping() {
        $this->addAlreadyExistingUserToCollection();
        $this->addToBeMappedUserToCollection();
        $this->generateCSV('to.be.mapped', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);

        $this->assertEqual(
            $this->collection->getUser('already.existing'),
            $new_collection->getUser('already.existing')
        );
    }

    public function itThrowsAnExceptionIfMappingFileDoesNotExist() {
        $this->expectException('User\XML\Import\MappingFileDoesNotExistException');

        $this->transformer->transform($this->collection, '/path/to/inexisting/file');
    }
}

class MappingFileOptimusPrimeTransformer_userUnknownInCollectionTest extends MappingFileOptimusPrimeTransformer_BaseTest {

    public function itTDoesNotThrowAnExceptionIfUserInMappingIsUnknownInCollectionSoThatWeCanReuseTheMappingFileInAnotherImport() {
        $this->generateCSV('unknown.user', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

}

class MappingFileOptimusPrimeTransformer_mapTest extends MappingFileOptimusPrimeTransformer_BaseTest {

    public function itDoesNotThrowAnExceptionWhenMapIsFilledWithAKnownUser() {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itDoesNotThrowAnExceptionWhenEmailDoesNotMatch() {
        $this->addEmailDoesNotMatchUserToCollection();

        $this->generateCSV('email.does.not.match', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionIsAlreadyExistingUser() {
        $this->addAlreadyExistingUserToCollection();

        $this->generateCSV('already.existing', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionToBeActivatedUser() {
        $this->addToBeActivatedUserToCollection();

        $this->generateCSV('to.be.activated', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionIsToBeCreatedUser() {
        $this->addToBeCreatedUserToCollection();

        $this->generateCSV('to.be.created', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsExceptionWhenThereIsATypoInTheAction() {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'mat:cstevens');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsExceptionWhenMapIsNotFilled() {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'map:');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsExceptionWhenMapIsFilledWithAnUnknownUser() {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'map:unknown_user');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }
}

class MappingFileOptimusPrimeTransformer_createTest extends MappingFileOptimusPrimeTransformer_BaseTest {

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionIsToBeCreatedUser() {
        $this->addToBeCreatedUserToCollection();

        $this->generateCSV('to.be.created', 'create');

        $this->transformer->transform($this->collection, $this->filename);
    }
}

class MappingFileOptimusPrimeTransformer_activateTest extends MappingFileOptimusPrimeTransformer_BaseTest {

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionIsToBeActivatedUser() {
        $this->addToBeActivatedUserToCollection();

        $this->generateCSV('to.be.activated', 'activate');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsAnExceptionWhenEmailDoesNotMatch() {
        $this->addEmailDoesNotMatchUserToCollection();

        $this->generateCSV('email.does.not.match', 'activate');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }
}
