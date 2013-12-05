<?php

namespace Zoop\Shard\Test\Serializer;

use Zoop\Shard\Manifest;
use Zoop\Shard\Test\BaseTest;
use Zoop\Shard\Serializer\Unserializer;
use Zoop\Shard\Test\Serializer\TestAsset\Document\User;
use Zoop\Shard\Test\Serializer\TestAsset\Document\Group;
use Zoop\Shard\Test\Serializer\TestAsset\Document\Profile;

class UnserializeModeTest extends BaseTest
{
    public function setUp()
    {
        $manifest = new Manifest(
            [
                'models' => [
                    __NAMESPACE__ . '\TestAsset\Document' => __DIR__ . '/TestAsset/Document'
                ],
                'extension_configs' => [
                    'extension.serializer' => true,
                    'extension.odmcore' => true
                ],
            ]
        );

        $this->documentManager = $manifest->getServiceManager()->get('modelmanager');
        $this->serializer = $manifest->getServiceManager()->get('serializer');
        $this->unserializer = $manifest->getServiceManager()->get('unserializer');
    }

    public function testUnserializePatchGeneral()
    {
        $documentManager = $this->documentManager;

        $user = new User();
        $user->setUsername('superdweebie');
        $user->setPassword('secret'); //uses Serialize Ignore annotation
        $user->defineLocation('here');
        $user->setProfile(new Profile('Tim', 'Roediger'));
        $user->setActive(true);

        $documentManager->persist($user);
        $documentManager->flush();

        $id = $user->getId();
        $documentManager->clear();
        /* @var $updated User */
        $updated = $this->unserializer->fromArray(
            [
                'id' => $id,
                'location' => 'there',
                'profile' => [
                    'firstname' => 'Tom'
                ],
                'active' => false
            ],
            'Zoop\Shard\Test\Serializer\TestAsset\Document\User'
        );

        $this->assertEquals('there', $updated->location());
        $this->assertEquals('superdweebie', $updated->getUsername());
        $this->assertEquals('Tom', $updated->getProfile()->getFirstname());
        $this->assertEquals(false, $updated->getActive());

        $documentManager->remove($updated);
        $documentManager->flush();
        $documentManager->clear();
    }

    public function testUnserializePatchAddItemToCollection()
    {
        $documentManager = $this->documentManager;

        $user = new User();
        $user->addGroup(new Group('groupA'));
        $user->addGroup(new Group('groupB'));

        $documentManager->persist($user);
        $documentManager->flush();

        $id = $user->getId();
        $data = $this->serializer->toArray($user);
        $groups = $data['groups'];
        $groups[] = [
            'name'=> 'groupC'
        ];
        $documentManager->clear();

        /* @var $updated User */
        $updated = $this->unserializer->fromArray(
            [
                'id' => $id,
                'groups' => $groups
            ],
            'Zoop\Shard\Test\Serializer\TestAsset\Document\User'
        );

        $this->assertCount(3, $updated->getGroups());

        $documentManager->remove($updated);
        $documentManager->flush();
        $documentManager->clear();
    }

    public function testUnserializePatchRemoveItemFromCollection()
    {
        $documentManager = $this->documentManager;

        $user = new User();
        $user->addGroup(new Group('groupA'));
        $user->addGroup(new Group('groupB'));

        $documentManager->persist($user);
        $documentManager->flush();

        $id = $user->getId();
        $data = $this->serializer->toArray($user);
        $groups = $data['groups'];
        unset($groups[1]);
        $documentManager->clear();

        /* @var $updated User */
        $updated = $this->unserializer->fromArray(
            [
                'id' => $id,
                'groups' => $groups
            ],
            'Zoop\Shard\Test\Serializer\TestAsset\Document\User'
        );

        $this->assertCount(1, $updated->getGroups());

        $documentManager->remove($updated);
        $documentManager->flush();
        $documentManager->clear();
    }

    public function testUnserializePatchEmptyCollection()
    {
        $documentManager = $this->documentManager;

        $user = new User();
        $user->addGroup(new Group('groupA'));
        $user->addGroup(new Group('groupB'));

        $documentManager->persist($user);
        $documentManager->flush();

        $id = $user->getId();
        $documentManager->clear();

        /* @var $updated User */
        $updated = $this->unserializer->fromArray(
            [
                'id' => $id,
                'groups' => []
            ],
            'Zoop\Shard\Test\Serializer\TestAsset\Document\User'
        );

        $this->assertCount(0, $updated->getGroups());

        $documentManager->remove($updated);
        $documentManager->flush();
        $documentManager->clear();
    }

    public function testUnserializeUpdateGeneral()
    {
        $documentManager = $this->documentManager;

        $user = new User();
        $user->setUsername('superdweebie');
        $user->setPassword('secret'); //uses Serialize Ignore annotation
        $user->defineLocation('here');
        $user->setProfile(new Profile('Tim', 'Roediger'));
        $user->setActive(true);

        $documentManager->persist($user);
        $documentManager->flush();
        $id = $user->getId();
        $documentManager->clear();

        /* @var $updated User */
        $updated = $this->unserializer->fromArray(
            [
                'id' => $id,
                'location' => 'there',
                'active' => false,
                'profile' => [
                    'firstname' => 'Tom'
                ]
            ],
            'Zoop\Shard\Test\Serializer\TestAsset\Document\User',
            null,
            Unserializer::UNSERIALIZE_UPDATE
        );

        $this->assertEquals('there', $updated->location());
        $this->assertEquals(false, $updated->getActive());
        $this->assertEquals(null, $updated->getUsername());
        $this->assertEquals('Tom', $updated->getProfile()->getFirstname());
        $this->assertEquals(null, $updated->getProfile()->getLastname());

        $documentManager->remove($updated);
        $documentManager->flush();
        $documentManager->clear();
    }

    public function testUnserializeUpdateAddItemToCollection()
    {
        $documentManager = $this->documentManager;

        $user = new User();
        $user->addGroup(new Group('groupA'));
        $user->addGroup(new Group('groupB'));

        $documentManager->persist($user);
        $documentManager->flush();
        $id = $user->getId();
        $data = $this->serializer->toArray($user);
        $groups = $data['groups'];
        $groups[] = [
            'name'=> 'groupC'
        ];
        $documentManager->clear();

        /* @var $updated User */
        $updated = $this->unserializer->fromArray(
            [
                'id' => $id,
                'groups' => $groups
            ],
            'Zoop\Shard\Test\Serializer\TestAsset\Document\User',
            null,
            Unserializer::UNSERIALIZE_UPDATE
        );

        $this->assertCount(3, $updated->getGroups());

        $documentManager->remove($updated);
        $documentManager->flush();
        $documentManager->clear();
    }

    public function testUnserializeUpdateRemoveItemFromCollection()
    {
        $documentManager = $this->documentManager;

        $user = new User();
        $user->addGroup(new Group('groupA'));
        $user->addGroup(new Group('groupB'));

        $documentManager->persist($user);
        $documentManager->flush();
        $id = $user->getId();
        $data = $this->serializer->toArray($user);
        $groups = $data['groups'];
        unset($groups[1]);
        $documentManager->clear();

        /* @var $updated User */
        $updated = $this->unserializer->fromArray(
            [
                'id' => $id,
                'groups' => $groups
            ],
            'Zoop\Shard\Test\Serializer\TestAsset\Document\User',
            null,
            Unserializer::UNSERIALIZE_UPDATE
        );

        $this->assertCount(1, $updated->getGroups());

        $documentManager->remove($updated);
        $documentManager->flush();
        $documentManager->clear();
    }

    public function testUnserializeUpdateEmptyCollection()
    {
        $documentManager = $this->documentManager;

        $user = new User();
        $user->addGroup(new Group('groupA'));
        $user->addGroup(new Group('groupB'));

        $documentManager->persist($user);
        $documentManager->flush();
        $id = $user->getId();
        $documentManager->clear();

        /* @var $updated User */
        $updated = $this->unserializer->fromArray(
            [
                'id' => $id,
                'groups' => []
            ],
            'Zoop\Shard\Test\Serializer\TestAsset\Document\User',
            null,
            Unserializer::UNSERIALIZE_UPDATE
        );

        $this->assertCount(0, $updated->getGroups());

        $documentManager->remove($updated);
        $documentManager->flush();
        $documentManager->clear();
    }
}
