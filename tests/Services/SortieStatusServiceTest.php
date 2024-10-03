<?php declare(strict_types=1);

namespace App\Tests\Services;


use App\Repository\SortieRepository;
use App\Service\SortieStatusService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SortieStatusServiceTest extends KernelTestCase
{

    private ?SortieRepository $sortieRepository = null;

    private ?SortieStatusService $sortieStatusService = null;

    protected function setUp(): void
    {
        self::bootKernel([
            'environment' => 'test',
        ]);
        $container = self::$kernel->getContainer();

        $this->sortieRepository = $container->get(SortieRepository::class);
        $this->sortieStatusService = $container->get(SortieStatusService::class);
    }


    public function testSomething(): void
    {
        $comptD = 0;
        $comptF = 0;
        $sorties = $this->sortieRepository->findAll();
        foreach ($sorties as $sortie) {
            if(($sortie->getEtat()->getId() == 2) &&
                ($sortie->getDateLimiteInscription() < new \DateTime('now')))
            {
                $comptD++;
            }
        }
        $this->sortieStatusService->checkSortieStatus();
        $sortiesF =  $this->sortieRepository->findAll();
        foreach ($sortiesF as $sortie) {
            if(($sortie->getEtat()->getId() == 2) &&
                ($sortie->getDateLimiteInscription() < new \DateTime('now')))
            {
                $comptF++;
            }
        }
        $result = $comptD - $comptF;

        $this->assertEquals(3,$comptD);
        $this->assertTrue($result >= 0);
        $this->assertEquals(3,$result);
    }
}