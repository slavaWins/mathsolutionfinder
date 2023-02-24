<?php


namespace MathSolutionFinder\Library;


use Illuminate\Support\Facades\Cache;
use phpseclib3\Math\BigInteger\Engines\BCMath\BuiltIn;


class MathSolutionFinder
{


    public $lastTry = 0;
    public $maxTry = 0;


    public $maxTryInOneLearn = null;


    public $savedResults = [];
    public $cashName = null;

    public $properties = [];
    public string $mode = "max";
    public $similarTarget;

    /**
     * @var callable
     */
    private $fun;
    private $useCache = null;
    /**
     * @var mixed
     */
    private $bestDataId;
    /**
     * @var mixed
     */
    private $bestResult;

    public static function New()
    {
        return new MathSolutionFinder();
    }

    public function SetMode()
    {
        return new MSF_ModeEnum($this);
    }


    public function GetPropertyVariantsByKey($key)
    {
        $tests = [];
        $data = $this->properties[$key];


        if ($data['type'] == 'range') {

            for ($i = 1; $i <= $data['count']; $i++) {
                // $delta = $data['to'] - $data['from'];
                $val = $data['from'] + $i * $data['stepSize'];
                if (is_int($data['from']) and is_int($data['to'])) $val = intval(round($val));
                $tests[] = $val;
            }
        }

        if ($data['type'] == 'options') {

            return $data['options'];
        }

        return $tests;
    }

    private $combinations = [];

    public function ComboLoop($keyNumber, $comboUpper, $out, $level = 0)
    {


        if (!isset($this->properties->keys()[$keyNumber])) dd("ERROR!");

        $propName = $this->properties->keys()[$keyNumber];

        $outsLocal = [];

        foreach ($this->GetPropertyVariantsByKey($propName) as $valueA) {


            $comboUpper[$propName] = $valueA;

            //$out[] = $comboUpper;

            if (count($this->properties) - 1 == $keyNumber) {

                // if(!in_array($comboUpper, $out)) {
                $out[] = $comboUpper;
                //   }
                continue;
            } else {


                $res = $this->ComboLoop($keyNumber + 1, $comboUpper, $out, $level + 1);

                // dump($out);
                $outsLocal = array_merge($outsLocal, $res);


            }

        }

        $out = array_merge($out, $outsLocal);
        return $out;
    }

    public function SetCallable(callable $fun)
    {
        $this->fun = $fun;
        return $this;
    }


    function IsBestResult($prev, $current)
    {

        if ($prev == null) return $current;

        if ($this->mode == "max") return $current > $prev;
        if ($this->mode == "min") return $current > $prev;

        if ($this->mode == "similar") {
            $distP = abs($this->similarTarget - $prev);
            $distC = abs($this->similarTarget - $current);
            return $distC < $distP;
        }

        return false;

    }


    public function GetChaheKey()
    {
        $h = "xvynb";
        $h .= count($this->properties);
        $h .= var_export($this->properties, true);
        $h .= $this->mode;
        $h = str_replace("\n", "", $h);
        $res = md5($h);
        return $res;
    }

    public function GetStatus()
    {

        return [
            'bestResult' => $this->bestResult,
            'maxTry' => $this->maxTry,
            'lastTry' => $this->lastTry,
            'percent' => round($this->lastTry / $this->maxTry * 100, 2),
        ];
    }

    public function Limit($maxTryInOneLearn)
    {

        $this->useCache = $this->GetChaheKey();
        $this->maxTryInOneLearn = $maxTryInOneLearn;

        return $this;
    }

    function Load()
    {
        if ($this->useCache) {
            $load = Cache::store('file')->get($this->useCache, null);

            if ($load) {
                $this->lastTry = $load['lastTry'];
                $this->maxTry = $load['maxTry'];
                $this->bestResult = $load['bestResult'];
                $this->bestDataId = $load['bestDataId'];
                $this->combinations = $load['combinations'];
                $this->savedResults = $load['savedResults'];

            }
        }

    }

    function Save()
    {
        if ($this->useCache) {


            $save = [
                'lastTry' => $this->lastTry,
                'maxTry' => $this->maxTry,
                'bestResult' => $this->bestResult,
                'bestDataId' => $this->bestDataId,
                'combinations' => $this->combinations,
                'savedResults' => $this->savedResults,

            ];


            Cache::store('file')->put($this->useCache, $save);
        }

    }

    function OnStop()
    {
        if ($this->useCache) {
            $this->Save();
        }
    }


    private $isInit = false;

    public function OnStart()
    {
        if ($this->isInit) return;

        $isLoaded = false;
        if ($this->useCache) {
            if ($this->Load()) {

            }
        }

        if ($this->combinations == null) {
            $this->combinations = $this->GenerateAllCombination(true);
            //$this->combinations = collect($this->combinations)->ran
        }
        $this->isInit = true;

        return $this;
    }

    public function DeltaTwoResults($id1, $id2)
    {
        $minData = $this->combinations[$id1];
        $maxData = $this->combinations[$id2];


        $deltaData = [];
        foreach ($minData as $K => $V) {
            if (is_string($V)) continue;
            $deltaData[$K] = $maxData[$K] - $V;
        }
        return $deltaData;
    }

    public function AddCombination($data)
    {
        $key = count($this->combinations);
        $this->combinations[$key] = $data;
        return $key;
    }

    public function AnalizWeight()
    {

        if ($this->mode <> 'max') {
            dump("Умею только max считать");
            return null;
        }

        $this->OnStart();


        if (!$this->bestResult) return "Пока что не с чем сравнивать";


        if ($this->lastTry < 2)  return "Пока что не с чем сравнивать";


        $minData = -1;
        $minValue = -1;
        $minKey = -1;
        $i = 0;
        while (true) {
            $i++;
            if ($i > 4) return  "Не удалось найти данные";

            $minKey = rand(0, $this->lastTry - 1);

            if ($minKey == $this->bestDataId) continue;

            if (!isset($this->savedResults[$minKey])) continue;
            $minValue = $this->savedResults[$minKey];

            if ($minValue == $this->bestResult) continue;


            //if(!isset( $this->combinations[$minKey]))continue;


            $minData = $this->combinations[$minKey];

            break;
        }


        $maxData = $this->combinations[$this->bestDataId];


        $delta = $this->DeltaTwoResults($minKey, $this->bestDataId);

        $data = $maxData;
        foreach ($data as $K => $V) {
            if (!isset($delta[$K])) continue;
            $V += $delta[$K];
            $V = min($this->properties[$K]['to'], $V);
            $V = max($this->properties[$K]['from'], $V);
            $data[$K] = $V;
        }



        if (in_array($data, $this->combinations)) {
            foreach ($this->combinations as $K => $V) {
                if ($minData == $V) {
                    if (isset($this->savedResults[$K])) {
                        return  "ПОпытка запустить тот же тест: " .$K;
                    }
                }
            }
        }


        $playResult = $this->PlayItem($data);

        if (!$playResult) return null;


        $out = [];


        $out = [
            'randData' => [
                'result' => $minValue,
                'data' => $minData,
            ],
            'bestData' => [
                'result' => $this->bestResult,
                'data' => $maxData,
            ],
            'delta' => $delta,
            'mathDeltaData' => $data,
            'mathDeltaResult' => $playResult[0] ?? null,
        ];

        if ($playResult[1]) {


            $this->bestResult = $playResult[0];
            $this->bestDataId = $this->AddCombination($data);
            $this->savedResults[$this->bestDataId] = $playResult[0];
            //  dump($this->bestDataId);
        }

        $this->OnStop();
        return $out;
    }

    function PlayItem($data)
    {
        $fun = $this->fun;
        $result = $fun($data);


        if ($result === null) return null;

        $isBest = false;
        if ($this->IsBestResult($this->bestResult, $result)) {
            $isBest = true;
        }

        return [$result, $isBest];
    }

    public function Learn()
    {
        $this->OnStart();


        $combos = $this->combinations;

        $this->maxTry = count($combos);

        $tryCount = 0;
        $start = $this->lastTry + 1;

        if($this->lastTry>=$this->maxTry-1){
            $this->lastTry=$this->maxTry;
            return [$this->bestResult, $combos[$this->bestDataId]];
        }

        for ($i = $start; $i < count($combos); $i++) {

            $data = $combos[$i];


            $response = $this->PlayItem($data);

            $this->lastTry = $i;

            if ($response) {

                $this->savedResults[$i] = $response[0] ?? null;

                if ($response[1]) {
                    $this->bestDataId = $i;
                    $this->bestResult = $response[0];
                }

            } else {

            }


            if ($this->maxTryInOneLearn) {
                $tryCount++;
                if ($tryCount >= $this->maxTryInOneLearn) {
                    break;
                }
            }
        }


        $this->OnStop();

        return [$this->bestResult, $combos[$this->bestDataId]];
    }

    public function GenerateAllCombination($isRandSort = false)
    {

        $tests = [];

        $this->properties = collect($this->properties);


        $res = $this->ComboLoop(0, [], [], 0);

        $out = $res;

        if ($isRandSort) {
            $out = [];
            $res = collect($res)->shuffle();

            foreach ($res as $V) {
                $out[] = $V;
            }

            return $out;
        }

        return $res;
    }

    public function AddPropertyRange($key, $from, $to, $count)
    {
        $stepSize = ($to - $from) / $count;
        $this->properties[$key] = ['from' => $from, 'to' => $to, 'count' => $count, 'stepSize' => $stepSize, 'type' => 'range'];
        return $this;
    }

    public function AddPropertyOptions($key, $options)
    {
        $this->properties[$key] = ['type' => 'options', 'options' => $options];
        return $this;
    }


}


class MSF_ModeEnum
{

    private MathSolutionFinder $me;

    public function __construct(MathSolutionFinder $me)
    {
        $this->me = $me;
    }

    public function Max()
    {
        $this->me->mode = "max";
        return $this->me;
    }

    public function Min()
    {
        $this->me->mode = "min";
        return $this->me;
    }

    public function Similar($targetValue)
    {
        $this->me->mode = "similar";
        $this->me->similarTarget = $targetValue;
        return $this->me;
    }
}
