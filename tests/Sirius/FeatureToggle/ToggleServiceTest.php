<?php
declare(strict_types=1);

namespace ApplicationTest\Service;

use CommonUtils\Sirius\FeatureToggle\ToggleService;
use PHPUnit_Framework_TestCase as TestCase;
use Qandidate\Toggle\Serializer\InMemoryCollectionSerializer;
use Qandidate\Toggle\ToggleManager;

class ToggleServiceTest extends TestCase
{
    public function testEnabledAndDisabled()
    {
        $testFeatures = [
                'feature1' => [
                    'name' => 'toggle1',
                    'conditions' => [],
                    'status' => 'inactive',
                ],
                'feature2' => [
                    'name' => 'toggle2',
                    'conditions' => [],
                    'status' => 'always-active',
                ],
        ];

        $collectionSerializer = new InMemoryCollectionSerializer();
        $collection = $collectionSerializer->deserialize($testFeatures);

        $sut = new ToggleService(
            new ToggleManager($collection)
        );

        self::assertEquals(false, $sut->isEnabled('toggle1'));
        self::assertEquals(true, $sut->isEnabled('toggle2'));

        $sut->enable('toggle1');
        $sut->disable('toggle2');

        self::assertEquals(true, $sut->isEnabled('toggle1'));
        self::assertEquals(false, $sut->isEnabled('toggle2'));
    }
}
