<?php
declare(strict_types=1);

namespace PhpList\PhpList4\Tests\Integration\Domain\Repository\Messaging;

use Doctrine\ORM\Proxy\Proxy;
use PhpList\PhpList4\Domain\Model\Identity\Administrator;
use PhpList\PhpList4\Domain\Model\Messaging\SubscriberList;
use PhpList\PhpList4\Domain\Repository\Identity\AdministratorRepository;
use PhpList\PhpList4\Domain\Repository\Messaging\SubscriberListRepository;
use PhpList\PhpList4\Tests\Integration\AbstractDatabaseTest;
use PhpList\PhpList4\Tests\Support\Traits\SimilarDatesAssertionTrait;

/**
 * Testcase.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class SubscriberListDatabaseTest extends AbstractDatabaseTest
{
    use SimilarDatesAssertionTrait;

    /**
     * @var string
     */
    const TABLE_NAME = 'phplist_list';

    /**
     * @var string
     */
    const ADMINISTRATOR_TABLE_NAME = 'phplist_admin';

    /**
     * @var SubscriberListRepository
     */
    private $subject = null;

    /**
     * @var AdministratorRepository
     */
    private $administratorRepository = null;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = $this->container->get(SubscriberListRepository::class);
        $this->administratorRepository = $this->container->get(AdministratorRepository::class);
    }

    /**
     * @test
     */
    public function findReadsModelFromDatabase()
    {
        $this->getDataSet()->addTable(self::TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $id = 1;
        $creationDate = new \DateTime('2016-06-22 15:01:17');
        $modificationDate = new \DateTime('2016-06-23 19:50:43');
        $name = 'News';
        $description = 'News (and some fun stuff)';
        $listPosition = 12;
        $subjectPrefix = 'phpList';
        $category = 'news';

        /** @var SubscriberList $model */
        $model = $this->subject->find($id);

        self::assertSame($id, $model->getId());
        self::assertEquals($creationDate, $model->getCreationDate());
        self::assertEquals($modificationDate, $model->getModificationDate());
        self::assertSame($name, $model->getName());
        self::assertSame($description, $model->getDescription());
        self::assertSame($listPosition, $model->getListPosition());
        self::assertSame($subjectPrefix, $model->getSubjectPrefix());
        self::assertTrue($model->isPublic());
        self::assertSame($category, $model->getCategory());
    }

    /**
     * @test
     */
    public function createsOwnerAssociationAsProxy()
    {
        $this->getDataSet()->addTable(
            self::ADMINISTRATOR_TABLE_NAME,
            __DIR__ . '/../Identity/Fixtures/Administrator.csv'
        );
        $this->getDataSet()->addTable(self::TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $subscriberListId = 1;
        $ownerId = 1;
        /** @var SubscriberList $model */
        $model = $this->subject->find($subscriberListId);
        $owner = $model->getOwner();

        self::assertInstanceOf(Administrator::class, $owner);
        self::assertInstanceOf(Proxy::class, $owner);
        self::assertSame($ownerId, $owner->getId());
    }

    /**
     * @test
     */
    public function creationDateOfNewModelIsSetToNowOnPersist()
    {
        $this->getDataSet()->addTable(self::TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $model = new SubscriberList();
        $expectedCreationDate = new \DateTime();

        $this->entityManager->persist($model);

        self::assertSimilarDates($expectedCreationDate, $model->getCreationDate());
    }

    /**
     * @test
     */
    public function modificationDateOfNewModelIsSetToNowOnPersist()
    {
        $this->getDataSet()->addTable(self::TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $model = new SubscriberList();
        $expectedModificationDate = new \DateTime();

        $this->entityManager->persist($model);

        self::assertSimilarDates($expectedModificationDate, $model->getModificationDate());
    }

    /**
     * @test
     */
    public function savePersistsAndFlushesModel()
    {
        $this->touchDatabaseTable(self::TABLE_NAME);

        $model = new SubscriberList();
        $this->subject->save($model);

        self::assertSame($model, $this->subject->find($model->getId()));
    }

    /**
     * @test
     */
    public function findByOwnerFindsSubscriberListWithTheGivenOwner()
    {
        $this->getDataSet()->addTable(
            self::ADMINISTRATOR_TABLE_NAME,
            __DIR__ . '/../Identity/Fixtures/Administrator.csv'
        );
        $this->getDataSet()->addTable(self::TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $owner = $this->administratorRepository->find(1);
        $ownedList = $this->subject->find(1);

        $result = $this->subject->findByOwner($owner);

        self::assertContains($ownedList, $result);
    }

    /**
     * @test
     */
    public function findByOwnerIgnoresSubscriberListWithOtherOwner()
    {
        $this->getDataSet()->addTable(
            self::ADMINISTRATOR_TABLE_NAME,
            __DIR__ . '/../Identity/Fixtures/Administrator.csv'
        );
        $this->getDataSet()->addTable(self::TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $owner = $this->administratorRepository->find(1);
        $foreignList = $this->subject->find(2);

        $result = $this->subject->findByOwner($owner);

        self::assertNotContains($foreignList, $result);
    }

    /**
     * @test
     */
    public function findByOwnerIgnoresSubscriberListFromOtherOwner()
    {
        $this->getDataSet()->addTable(
            self::ADMINISTRATOR_TABLE_NAME,
            __DIR__ . '/../Identity/Fixtures/Administrator.csv'
        );
        $this->getDataSet()->addTable(self::TABLE_NAME, __DIR__ . '/Fixtures/SubscriberList.csv');
        $this->applyDatabaseChanges();

        $owner = $this->administratorRepository->find(1);
        $unownedList = $this->subject->find(3);

        $result = $this->subject->findByOwner($owner);

        self::assertNotContains($unownedList, $result);
    }
}