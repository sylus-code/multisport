<?php

namespace App\Command;

use App\Entity\Point as PointEntity;
use App\Entity\Type as TypeEntity;
use App\Entity\Type;
use App\Repository\PointRepository;
use App\Repository\TypeRepository;
use App\Repository\WorkoutRepository;
use SylusCode\MultiSport\Workout\Point;
use App\Entity\Workout as WorkoutEntity;
use SylusCode\MultiSport\EndomondoWrapper\WorkoutImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SylusCode\MultiSport\Workout\Workout;


class ImportWorkoutCommand extends Command
{
    protected static $defaultName = 'import:workouts';

    private $workoutImporter;
    private $pathToZip;
    private $pathToExtract;
    private $typeRepository;
    private $workoutRepository;
    private $pointRepository;

    function __construct(
        WorkoutImporter $workoutImporter,
        string $pathToZip,
        string $pathToExtract,
        TypeRepository $typeRepository,
        WorkoutRepository $workoutRepository,
        PointRepository $pointRepository
    )
    {
        $this->workoutImporter = $workoutImporter;
        $this->pathToZip = $pathToZip;
        $this->pathToExtract = $pathToExtract;
        $this->typeRepository = $typeRepository;
        $this->workoutRepository = $workoutRepository;
        $this->pointRepository = $pointRepository;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import workouts from zip file')
            ->setHelp('This command lets you parse workout files to multisport-workout object');

    }

    function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(['Import starting...', '==================']);

        $counter = 0;
        foreach ($this->workoutImporter->importFromZipFile($this->pathToZip, $this->pathToExtract) as $item) {
            $output->writeln('ogarniam workout ' . $counter);
            $output->writeln('sprawdzam czy workout istnieje');
            $isExist = $this->isWorkoutExist($item);

            if ($isExist) {
                $output->writeln('obiekt istnieje skipuje i ide dalej');
                continue;
            }

            $output->writeln('workout nie istnieje');
            $output->writeln('mapowanie workoutu do encji');

            $workoutEntity = $this->mapWorkoutToEntity($item);

            $output->writeln('persistuje obiekt');
            $this->workoutRepository->persist($workoutEntity);

            if ($counter % 5 == 0) {
                $output->writeln('2 iteracja flushuje i wylaczam ');
                $this->workoutRepository->flush();

            }
            $output->writeln('koniec iteracji ' . $counter);
            $counter++;
        }
        $this->workoutRepository->flush();

        $output->writeln(['Import finished', '===============', sprintf('Successfully imported %d workouts!', $counter)]);

        return Command::SUCCESS;
    }

    private function mapWorkoutToEntity(Workout $workout): WorkoutEntity
    {
        $workoutEntity = new WorkoutEntity();

        $workoutEntity->setStart($workout->getStart());
        $workoutEntity->setMessage($workout->getMessage());
        $workoutEntity->setSteps($workout->getSteps());
        $workoutEntity->setMaxSpeed($workout->getMaxSpeed());
        $workoutEntity->setAvgSpeed($workout->getAvgSpeed());

        $workoutEntity->setMaxHeartRate($workout->getMaxHeartRate());
        $workoutEntity->setAvgHeartRate($workout->getAvgHeartRate());
        $workoutEntity->setDistance($workout->getDistance());
        $workoutEntity->setCalories($workout->getCalories());
        $workoutEntity->setDurationActive($workout->getDurationActive());

        $workoutEntity->setDurationTotal($workout->getDurationTotal());
        $workoutEntity->setType($this->getTypeByName($workout->getType()->getName()));


        foreach ($workout->getPoints() as $trackpoint) {
            $point = $this->mapWorkoutPointToEntity($trackpoint);
            $point->setWorkout($workoutEntity);
            $this->pointRepository->persist($point);
            $workoutEntity->addPoint($point);
            $this->workoutRepository->persist($workoutEntity);
        }


        return $workoutEntity;
    }

    private function mapWorkoutPointToEntity(Point $point): PointEntity
    {
        $pointEntity = new PointEntity();
        $pointEntity->setTime($point->getTime());
        $pointEntity->setDistance($point->getDistance());
        $pointEntity->setHeartRate($point->getHeartRate());
        $pointEntity->setAltitude($point->getAltitude());
        $pointEntity->setLongitude($point->getLongtitude());
        $pointEntity->setLatitude($point->getLatitude());
        $pointEntity->setSpeed($point->getSpeed());

        return $pointEntity;
    }

    private function getTypeByName(string $name): TypeEntity
    {
        $type = $this->typeRepository->findOneBy(["name" => $name]);

        if ($type) {
            return $type;
        }
        $type = new TypeEntity($name);
        $this->typeRepository->persist($type);
        $this->workoutRepository->flush();

        return $type;
    }

    private function isWorkoutExist(Workout $workout): bool
    {
        $startTime = $workout->getStart();

        return $this->workoutRepository->findOneBy(["start" => $startTime]) != null;
    }
}
