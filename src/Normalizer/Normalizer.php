<?php

namespace App\Normalizer;

use App\Entity\Workout;

class Normalizer
{
    public function normalize(Workout $workout): array
    {
        $workoutArray = [
            'start' => $workout->getStart()->format('Y-m-d H:i:s'),
            'type' => $workout->getType()->getName(),
            'distance' => $workout->getDistance(),
            'durationTotal' => $workout->getDurationTotal(),
            'calories' => $workout->getCalories(),
            'avgHearRate' => $workout->getAvgHeartRate(),
            'avgSpeed' => $workout->getAvgSpeed(),
            'message' => $workout->getMessage(),
            'steps' => $workout->getSteps(),
            'maxSpeed' => $workout->getMaxSpeed(),
            'maxHeartRate' => $workout->getMaxHeartRate(),
            'durationActive' => $workout->getDurationActive()
        ];

        foreach ($workout->getPoints() as $point) {

            $janek = [
                'distance' => $point->getDistance(),
                'speed' => $point->getSpeed(),
                'latitude' => $point->getLatitude(),
                'longitude' => $point->getLongitude(),
                'altitude' => $point->getAltitude(),
                'heartRate' => $point->getHeartRate()
            ];

            if ($point->getTime() !== null) {
                $janek['time'] = $point->getTime()->format('Y-m-d H:i:s');

            }

            $workoutArray['points'][] = $janek;
        }

        return $workoutArray;
    }
}