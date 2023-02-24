<?php


namespace MathSolutionFinder\Library;



class PredictSolutionFinder
{

    public static function PredicatInfo($lastTake)
    {

        $response = new PredicatResult();

        $fullVal = 0;
        $trent = [];
        $prev = null;
        $midle = 0;

        foreach ($lastTake as $K => $V) {

            $fullVal += $V;
            if ($prev != null) {
                $trent[$K] = $V - $prev;
                $midle += $trent[$K];
            }
            $prev = $V;
        }

        $last = $prev;

        $response->trentArray = $trent;
        $response->midleTrent = $midle / $last * 100;
        $response->arrow = $midle / count($trent) / $last * 100;
        $response->midleValueForAllPeriod = $fullVal / count($lastTake);
        $response->predictValue = $last + $last * (    $response->arrow /100);


        return $response;
    }

    public static function Example()
    {

        dump("UP TRATE");
        $data = [
            '1 day' => 1001,
            '2 day' => 1011,
            '3 day' => 1010,
            '4 day' => 1070,
            '5 day' => 1072,
        ];
        $result = self::PredicatInfo($data);

        dump($result);

        dump("negatrive TRATE");
        $data = [
            '1 day' => 1001,
            '2 day' => 971,
            '3 day' => 950,
            '4 day' => 961,
            '5 day' => 948,
        ];
        $result = self::PredicatInfo($data);

        dd($result);
    }
}

/** @property float arrow предсказание след значения в процентах */
/** @property float midleTrent общее направление тренда в процентах */
/** @property float $predictValue следущие значение будет */
/** @property float $trentArray это как изменялись значения, типа делтьта массив */
class PredicatResult
{
    public $predictValue;
    public $midleTrent;


    public $arrow;
    public $trentArray;
}
