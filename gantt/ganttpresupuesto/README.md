# Laravel Gantt Chart

[![License](https://poser.pugx.org/swatkins/laravel-gantt/license)](https://packagist.org/packages/swatkins/laravel-gantt)

A Laravel 5.x package to display items within a Gantt chart (adapted from [bastianallgeier/gantti](https://github.com/bastianallgeier/gantti)).

![Screenshot](https://github.com/swatkins/laravel-gantt/raw/master/src/assets/screenshot-gantt.png)

## Installation

Require this package with composer:

```shell
composer require swatkins/laravel-gantt
```

After updating composer, add the ServiceProvider to the providers array in config/app.php

```php
Swatkins\LaravelGantt\GanttServiceProvider::class,
```

Copy the package css file to your local css with the publish command:

```shell
php artisan vendor:publish --tag="gantt"
```

## Usage

The model to display in the Gantt Chart will need to have properties of `label`, `start` and `end` at minimum.

* `label` is the string to display for the item
* `start` is a date or datetime (will need to pass this as a YYYY-MM-DD format)
* `end` is a date or datetime (will need to pass this as a YYYY-MM-DD format)

```php
/**
 * Get your model items however you deem necessary
 */
$select = 'title as label, DATE_FORMAT(start, \'%Y-%m-%d\') as start, DATE_FORMAT(end, \'%Y-%m-%d\') as end';
$projects = \App\Project::select(\Illuminate\Support\Facades\DB::raw($select))
                ->orderBy('start', 'asc')
                ->orderBy('end', 'asc')
                ->get();
    
/**
 *  You'll pass data as an array in this format:
 *  [
 *    [ 
 *      'label' => 'The item title',
 *      'start' => '2016-10-08',
 *      'end'   => '2016-10-14'
 *    ]
 *  ]
 */
 
$gantt = new Swatkins\LaravelGantt\Gantt($projects->toArray(), array(
    'title'      => 'Demo',
    'cellwidth'  => 25,
    'cellheight' => 35
));

return view('gantt')->with([ 'gantt' => $gantt ]);
```

### Display in your view

In your view, add the `gantt.css` file:

```html
<link href="/vendor/swatkins/gantt/css/gantt.css" rel="stylesheet" type="text/css">
```

And then output the gantt HTML:

```html
{!! $gantt !!}
```

## Model Factory

Here is a factory for creating test data for your projects. You can paste this into your `database/factories/ModelFactory.php` file and then run this via `tinker`. See <https://laravel.com/docs/5.2/seeding#using-model-factories>.

```php
$factory->define(App\Project::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->sentence(),
        'start' => $faker->dateTimeBetween('-30 days'),
        'end' => $faker->dateTimeBetween('now', '+30 days')
    ];
});
```

## Attribution

This code is adapted from https://github.com/bastianallgeier/gantti

## License: 

MIT License - <http://www.opensource.org/licenses/mit-license.php>
