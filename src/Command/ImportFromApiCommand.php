<?php

namespace App\Command;

use App\Dictionary\EndomondoWorkoutType;
use App\Entity\Point as PointEntity;
use App\Entity\Tag;
use App\Entity\Type as TypeEntity;
use App\Entity\Type;
use App\Repository\PointRepository;
use App\Repository\TagRepository;
use App\Repository\TypeRepository;
use App\Repository\WorkoutRepository;
use Fabulator\Endomondo\EndomondoAPIBase;
use App\Entity\Workout as WorkoutEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ImportFromApiCommand extends Command
{
    protected static $defaultName = 'import:fromapi';

    const URL = 'https://www.endomondo.com/rest/v1/users/%s/workouts/history?expand=workout,points&after=%s&before=%s&limit=20&offset=%s';

    private $userId;
    private $email;
    private $password;

    const AFTER_DATE = '%s-01-01';
    const BEFORE_DATE = '%s-12-31';

    private $year = 2017;
    private $finishedYear = 2020;

    private $offset = 0;
    private $total = 0;

    private $typeRepository;
    private $workoutRepository;
    private $pointRepository;
    private $apiWrapper;
    private $authorized = false;
    private $tagRepository;

    public function __construct(
        TypeRepository $typeRepository,
        WorkoutRepository $workoutRepository,
        PointRepository $pointRepository,
        TagRepository $tagRepository
    ) {
        $this->typeRepository = $typeRepository;
        $this->workoutRepository = $workoutRepository;
        $this->pointRepository = $pointRepository;
        $this->tagRepository = $tagRepository;

        $this->apiWrapper = new EndomondoAPIBase();
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED)
            ->addOption('password', null, InputOption::VALUE_REQUIRED)
            ->addOption('userid',null, InputOption::VALUE_REQUIRED)
            ->setDescription('Import workouts from endomondo api')
            ->setHelp('This command lets you parse workout object from endomondo to multisport-workout object');

    }

    function execute(InputInterface $input, OutputInterface $output)
    {
        $this->userId = $input->getOption('userid');
        $this->email = $input->getOption('email');
        $this->password = $input->getOption('password');

        $output->writeln(['Import starting...', '==================']);

        while (true) {

            $result = $this->request(
                sprintf(self::AFTER_DATE, $this->year),
                sprintf(self::BEFORE_DATE, $this->year),
                $this->offset
            );

            $this->total = $result['paging']['total'];

            $output->writeln('[NOWY WHILE]');
            $output->writeln('aktualny total to: ' . $this->total);
            $output->writeln('aktualny total rok: ' . $this->year);
            $output->writeln('aktualny offset: ' . $this->offset);

            // zatrzymaj jesli doszlismy juz do konca
            if (
                $this->year == $this->finishedYear
                && $this->offset >= $this->total
            ) {
                break;
            }

            // przejdz na nastepny rok
            if (empty($result['data'])) {

                $output->writeln('pusta data');
                $this->year++;
                $this->offset = 0;
                continue;
            }


            foreach ($result['data'] as $key => $item) {

                $isExist = $this->isWorkoutExist($this->resolveTimestamp($item['local_start_time']));

                if ($isExist) {
                    $output->writeln('obiekt istnieje skipuje i ide dalej');
                    continue;
                }
                $workoutEntity = $this->mapWorkoutToEntity($item, $this->userId);
                $this->workoutRepository->persist($workoutEntity);
                if ($key % 5 == 0) {
                    $this->workoutRepository->flush();
                }
            }
            $this->workoutRepository->flush();
            $this->offset = $this->offset + 20;
        }
        return Command::SUCCESS;
    }


    private function request(string $after, string $before, int $offset): array
    {
        if ($this->authorized == false) {
            $this->apiWrapper->login($this->email, $this->password);
            $this->authorized = true;
        }

        $result = $this->apiWrapper->send(
            'GET',
            sprintf(
                self::URL,
                $this->userId,
                $after,
                $before,
                $offset
            )
        );

        return json_decode((string) $result->getBody(), true);
    }

    private function mapWorkoutToEntity(array $workout, int $userId): WorkoutEntity
    {
        $workoutEntity = new WorkoutEntity();
        $workoutEntity->setUserId($userId);

        if (isset($workout['local_start_time'])){
            $workoutEntity->setStart($this->resolveTimestamp($workout['local_start_time']));
        }
        if (isset($workout['notes'])){
            $workoutEntity->setMessage($workout['notes']);
        }
        if (isset($workout['steps'])){
            $workoutEntity->setSteps($workout['steps']);
        }
        if (isset($workout['speed_max'])){
            $workoutEntity->setMaxSpeed($workout['speed_max']);
        }
        if (isset($workout['speed_avg'])){
            $workoutEntity->setAvgSpeed($workout['speed_avg']);
        }
        if (isset($workout['heart_rate_max'])){
            $workoutEntity->setMaxHeartRate($workout['heart_rate_max']);
        }
        if (isset($workout['heart_rate_avg'])){
            $workoutEntity->setAvgHeartRate($workout['heart_rate_avg']);
        }
        if (isset($workout['distance'])){
            $workoutEntity->setDistance($workout['distance']);
        }
        if (isset($workout['calories'])){
            $workoutEntity->setCalories($workout['calories']);
        }
        if (isset($workout['duration'])){
            $workoutEntity->setDurationTotal($workout['duration']);
        }

        $workoutEntity->setType($this->getTypeByName(EndomondoWorkoutType::getName($workout['sport'])));

        if (isset($workout['hashtags'])){
            foreach ($workout['hashtags'] as $tagName){
                $tag = new Tag($tagName);
                $tag->addWorkout($workoutEntity);
                $workoutEntity->addTag($tag);
                $this->tagRepository->persist($tag);
                $this->workoutRepository->persist($workoutEntity);
            }
        }

        if (isset($workout['points']) && isset($workout['points']['points'])) {
            foreach ($workout['points']['points'] as $trackpoint) {

                $point = $this->mapWorkoutPointToEntity($trackpoint);
                $point->setWorkout($workoutEntity);
                $this->pointRepository->persist($point);
                $workoutEntity->addPoint($point);
                $this->workoutRepository->persist($workoutEntity);
            }
        }

        return $workoutEntity;
    }

    private function mapWorkoutPointToEntity(array $point): PointEntity
    {
        $pointEntity = new PointEntity();
        if (isset($point['distance'])){
            $pointEntity->setDistance($point['distance']);
        }
        if (isset($point['time'])){
            $pointEntity->setTime($this->resolveTimestamp($point['time']));
        }
        if (isset($point['sensor_data']['heart_rate'])){
            $pointEntity->setHeartRate($point['sensor_data']['heart_rate']);
        }
        if (isset($point['altitude'])){
            $pointEntity->setAltitude($point['altitude']);
        }
        if (isset($point['longitude'])){
            $pointEntity->setLongitude($point['longitude']);
        }
        if (isset($point['latitude'])){
            $pointEntity->setLatitude($point['latitude']);
        }
        if (isset($point['speed'])){
            $pointEntity->setSpeed($point['speed']);
        }

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

    private function isWorkoutExist(\DateTime $startTime): bool
    {
        $exist = $this->workoutRepository->findOneBy(["start" => $startTime, "userId" => $this->userId]);

        if ($exist) {
            return true;
        } else return false;
    }

    private function resolveTimestamp(string $timestamp)
    {
        $timestamp = str_replace('.000', ' ', $timestamp);
        $datetime = \DateTime::createFromFormat(DATE_ATOM, $timestamp);

        return $datetime;
    }
}
