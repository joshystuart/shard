<?php

namespace Zoop\Shard\Test\Freeze;

use Zoop\Shard\Manifest;
use Zoop\Shard\Test\BaseTest;
use Zoop\Shard\Test\Freeze\TestAsset\Document\Stamped;
use Zoop\Shard\Test\TestAsset\User;

class StampTest extends BaseTest {

    public function setUp(){

        $manifest = new Manifest([
            'documents' => [
                __NAMESPACE__ . '\TestAsset\Document' => __DIR__ . '/TestAsset/Document'
            ],
            'extension_configs' => [
                'extension.freeze' => true
            ],
            'document_manager' => 'testing.documentmanager',
            'service_manager_config' => [
                'factories' => [
                    'testing.documentmanager' => 'Zoop\Shard\Test\TestAsset\DocumentManagerFactory',
                    'user' => function(){
                        $user = new User();
                        $user->setUsername('toby');
                        return $user;
                    }
                ]
            ]
        ]);

        $this->documentManager = $manifest->getServiceManager()->get('testing.documentmanager');
        $this->freezer = $manifest->getServiceManager()->get('freezer');
    }

    public function testStamps() {

        $documentManager = $this->documentManager;
        $testDoc = new Stamped();
        $testDoc->setName('version1');

        $documentManager->persist($testDoc);
        $documentManager->flush();
        $id = $testDoc->getId();
        $documentManager->clear();

        $repository = $documentManager->getRepository(get_class($testDoc));
        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertNull($testDoc->getFrozenBy());
        $this->assertNull($testDoc->getFrozenOn());
        $this->assertNull($testDoc->getThawedBy());
        $this->assertNull($testDoc->getThawedOn());

        $this->freezer->freeze($testDoc);

        $documentManager->flush();
        $documentManager->clear();

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertEquals('toby', $testDoc->getFrozenBy());
        $this->assertNotNull($testDoc->getFrozenOn());
        $this->assertNull($testDoc->getThawedBy());
        $this->assertNull($testDoc->getThawedOn());

        $this->freezer->thaw($testDoc);

        $documentManager->flush();
        $documentManager->clear();

        $testDoc = null;
        $testDoc = $repository->find($id);

        $this->assertEquals('toby', $testDoc->getFrozenBy());
        $this->assertNotNull($testDoc->getFrozenOn());
        $this->assertEquals('toby', $testDoc->getThawedBy());
        $this->assertNotNull($testDoc->getThawedOn());
    }
}