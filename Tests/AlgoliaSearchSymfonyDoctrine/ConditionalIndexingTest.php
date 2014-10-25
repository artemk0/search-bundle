<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests;

class ConditionalIndexingTest extends BaseTest
{
    public function beforeEach()
    {
        $this->getIndexer()->reset();
    }

    public function testNewProductThatShouldNotBeIndexedIsNotIndexed()
    {
        $product = new Entity\ProductWithConditionalIndexing();
        $product
        ->setName('The Ultimate Algolia Userguide')
        ->setShortDescription('Learn to master your search engine and drive sales up!')
        ->setPrice(0); // this should prevent indexing

        $this->persistAndFlush($product);
        $this->assertEquals(
            array(),
            $this->getIndexer()->creations
        );

        return $product;
    }

    public function testNewProductThatShouldBeIndexedIsIndexed()
    {
        $product = new Entity\ProductWithConditionalIndexing();
        $product
        ->setName('The Ultimate Algolia Userguide')
        ->setShortDescription('Learn to master your search engine and drive sales up!')
        ->setPrice(9);

        $this->persistAndFlush($product);
        $this->assertEquals(
            array(
                'ProductWithConditionalIndexing_dev' => array(
                    array(
                        'name' => 'The Ultimate Algolia Userguide',
                        'objectID' => (string)$product->getId()
                    )
                )
            ),
            $this->getIndexer()->creations
        );

        return $product;
    }

    /**
     * @depends testNewProductThatShouldBeIndexedIsIndexed
     */
    public function testProductThatWasIndexedIsUnindexedOnUpdate($product)
    {
        $product->setPrice(0);
        $this->persistAndFlush($product);

        $this->assertEquals(
            array(
                'ProductWithConditionalIndexing_dev' => array(
                    (string)$product->getId()
                )
            ),
            $this->getIndexer()->deletions
        );
        $this->assertEquals(
            array(),
            $this->getIndexer()->updates
        );
        $this->assertEquals(
            array(),
            $this->getIndexer()->creations
        );
    }

    /**
     * @depends testNewProductThatShouldNotBeIndexedIsNotIndexed
     */
    public function testProductThatWasNotIndexedIsIndexedOnUpdate($product)
    {
        $product->setPrice(10);
        $this->persistAndFlush($product);

        $this->assertEquals(
            array('ProductWithConditionalIndexing_dev' => array(
                    array(
                        'name' => 'The Ultimate Algolia Userguide',
                        'objectID' => (string)$product->getId()
                    )
                )
            ),
            $this->getIndexer()->creations
        );
        $this->assertEquals(
            array(),
            $this->getIndexer()->updates
        );
        $this->assertEquals(
            array(),
            $this->getIndexer()->deletions
        );
    }

    public function testProductThatWasIndexedIsUnindexedEvenIfChangedButNotFlushed()
    {
        $product = new Entity\ProductWithConditionalIndexing();
        $product
        ->setName('I\'m a pathological case.')
        ->setPrice(10)
        ->setShortDescription('Aww\'right');
        $this->persistAndFlush($product);

        // Check we indexed the product OK.
        $this->assertNotEmpty($this->getIndexer()->creations);

        // This would normally prevent indexing
        $product->setPrice(0);

        $this->getIndexer()->reset();

        $objectID = (string)$product->getId();

        $this->removeAndFlush($product);

        // But since the engine is clever, it should notice that
        // even though the condition for indexing is now false,
        // it was true when the object was last synced with the DB,
        // so it will unindex it.

        $this->assertEquals(
            array('ProductWithConditionalIndexing_dev' => array($objectID)),
            $this->getIndexer()->deletions
        );
    }
}
