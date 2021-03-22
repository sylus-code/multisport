<?php

namespace App\Controller;

use App\Normalizer\Normalizer;
use App\Repository\WorkoutRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


class GetWorkoutsController
{
    private $workoutRepo;
    private $normalizer;

    public function __construct(WorkoutRepository $workoutRepo, Normalizer $normalizer)
    {
        $this->workoutRepo = $workoutRepo;
        $this->normalizer = $normalizer;
    }
    /**
     * @Route( path="/workout" , name="get_workout", methods={"GET"})
     * @return JsonResponse
     * @throws
     */
    public function action(): JsonResponse
    {
        $workouts = $this
            ->workoutRepo
            ->findAll();

        if (!$workouts){
            return new JsonResponse([], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse(array_map([$this->normalizer, 'normalize'], $workouts));;
    }
}