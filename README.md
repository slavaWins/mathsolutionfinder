<p align="center">
<img src="info/logo.jpg">
</p>

## MathSolutionFinder

Кароч изи пакет для матиматической гавна комбинаторики помойных данных на пхп.

## Установка из composer

```  
composer require slavawins/mathsolutionfinder
```

## Просто пример использования
Самое простое что я придумал это найти максимальное значение между двуми перемножиными числами. Понятно и так что ответ будет - два переменоженных числа.

```  
use MathSolutionFinder\Library\MathSolutionFinder;

        $module = MathSolutionFinder::New()->SetMode()->Max()
            ->AddPropertyRange("one", 1, 100, 6)
            ->AddPropertyRange("two", 1, 5, 10)
            ->SetCallable(function ($data) {
                return $data['one'] * $data['two'];
            });

        $result = $module->Learn();
        dump($result);        
        
```

Получится вот такой результат:

    array:2 [
          0 => 500
          1 => array:2 [
            "one" => 100
            "two" => 5
          ]
    ]

Мы создаем класс. И говорим что хотим высчитать максимальную сумму.
$module = MathSolutionFinder::New()->SetMode()->Max()


Далее с помощью: ->AddPropertyRange("one", 1, 100, 6) Добавлем свойство которое нужно рассчитывать. Назвываться оно будет one и будет иметь значение от 1 до 100. 6 это сколько вариантов для этого числа нужно для рассчетов.
Чем больше число, тем точнее результат. Но больше нагрузка.

Затем вываем   ->SetCallable(function ($data) { всё что внутри этой функции это рассчет, он будет выполнятся пока не переберутся все комбинации свойств.

   

Что бы понимать состояние рассчетов, можно воспользоваться такой функцией: dump($module->GetStatus());

    array:4 [
        "bestResult" => 500
        "maxTry" => 60
        "lastTry" => 59
        "percent" => 98.33
    ]

Она покажет лучший получившийся результат bestResult.  И maxTry -  сколько попыток выполнено.


Пример поиска конкретного значения. Допустим мы хотим найти значения свойств чтоб на выходе оплучить число 320

```
        $module = MathSolutionFinder::New()->SetMode()->Similar(320.525)
            ->AddPropertyRange("one", 1, 100, 6)
            ->AddPropertyRange("two", 1, 5, 10)
            ->SetCallable(function ($data) {
                return $data['one'] * $data['two'];
            });
        
        $result = $module->Learn();

        dump($result);
        
```
С учетом шага мы получим такой результат: "bestResult" => 335


Увиличим количество шагов и разрешим использовать не целые числа:

        $module = MathSolutionFinder::New()->SetMode()->Similar(320.525)
            ->AddPropertyRange("one", 1.1, 100.2, 416)
            ->AddPropertyRange("two", 1, 5, 410)
            ->SetCallable(function ($data) {
                return $data['one'] * $data['two'];
            });

        $result = $module->Learn();

Тогда мы получим такой результат:  <BR>
"bestResult" => 320.75769230769 <BR>
"maxTry" => 170560

Да, было проверено 170560 комбинаций, чтоб решить простое уровнение)


## Кэширование

Если мы используем функцию лимит. То включится решим файлового кэширования. 
        $module->Limit(10);
При вызове Learn() будет проверяться 10 комбинаций. Данные сами сейвятся, между запросами. Можно в крон запустить или в консоли.


## Кэширование и оптимизация рассчета
Это самая крутая часть этого пакета, то ради его вообще качать можно. 
Этот пример позволяет запустить его несколько раз подряд, и он не будет начинать рассчеты с самого начала, будет продолжать с места где закончил. Каждый раз по 10 комбинаций проверять.
Но кроме того у него есть функция $module->AnalizWeight(); Эта штука берет несколько сохраненных результатов, сравнивает их, и предполагает какие значения нужно подставить что бы получить результат ещё лучше. Для некоторых рассчетов это может сократить обучение в разы!



        $module = MathSolutionFinder::New()->SetMode()->Max()
            ->AddPropertyRange("one", 1.1, 100, 55)
            ->AddPropertyRange("two", 1, 50, 55)
            ->SetCallable(function ($data) {
                return $data['one'] * $data['two'];
            });

        $module->Limit(10);
        $result = $module->Learn();
        $optim = $module->AnalizWeight();

        dump($result);
        dump($optim);
        dump($module->GetStatus());

    
    array:2 [
    0 => 3878.8236363636
    1 => array:2 [
    "one" => 94.605454545455
    "two" => 41
    ]
    ]
    array:5 [
    "randData" => array:2 [
    "result" => 263.25272727273
    "data" => array:2 [
    "one" => 15.485454545455
    "two" => 17
    ]
    ]
    "bestData" => array:2 [
    "result" => 3878.8236363636
    "data" => array:2 [
    "one" => 94.605454545455
    "two" => 41
    ]
    ]
    "delta" => array:2 [
    "one" => 79.12
    "two" => 24
    ]
    "mathDeltaData" => array:2 [
    "one" => 100
    "two" => 50
    ]
    "mathDeltaResult" => array:2 [
    0 => 5000
    1 => true
    ]
    ]
    array:4 [
    "bestResult" => 5000
    "maxTry" => 3025
    "lastTry" => 10
    "percent" => 0.33
    ]
    "ok"


Перевожу что написано в этих логах: ПОсле первой пыптке было найдено число 3878. (ПОтому что комбинации рандомно запускаются).
Затем был запущен AnalizWeight и он предположил что нужно подставить числа 100 и 50. И сразу же режшил задачу. На 10 попытках из 3025.
Такая оптимизация не будет давать сразу правильный ответ, но с помощью предположений сможет быстро найти не плохой вариант.
