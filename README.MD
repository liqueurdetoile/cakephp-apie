![CI](https://github.com/liqueurdetoile/cakephp-apie/actions/workflows/ci.yml/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/liqueurdetoile/cakephp-apie/badge.svg)](https://coveralls.io/github/liqueurdetoile/cakephp-apie)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

<p align="center"><a href="https://liqueurdetoile.com" target="\_blank"><img src="https://hosting.liqueurdetoile.com/logo_lqdt.png" alt="Liqueur de Toile"></a></p>

# Cake as a Pie

**This is a CakePHP plugin for 4.x branch.**

The main idea of this plugin is to remotely leverage power of CakePHP query builder to configure advanced data queries through inbound URL parameter. Except for pagination, CakePHP framework is lacking tools to quicky achieve these goals. The plugin by itself is fairly agnostic about endpoints routing logic.

- [Cake as a Pie](#cake-as-a-pie)
  - [Quick overview](#quick-overview)
  - [Installation](#installation)
  - [Component options](#component-options)
  - [Component methods](#component-methods)
  - [Query descriptor syntax](#query-descriptor-syntax)
    - [Query expressions](#query-expressions)
    - [SQL functions](#sql-functions)
    - [Closures](#closures)

## Quick overview

Let's say you want to fetch paginated articles written in 2020 for a given an author depending on its name and sort results by written date. Here's what you may do **without** the plugin in your endpoint :

```php
// In your index method of your ArticlesController endpoint
// URL may be /api/v1/articles?author_name=smith&from=2020-01-01&to=2020-12-31 in a REST context for instance
$name = $this->request->getQuery('author_name');
$from = $this->request->getQuery('from');
$to = $this->request->getQuery('to');

$query = $this->Articles
    ->find()
    ->innerJoinWith('Authors', function($q) use ($name) {
        return $q->where(['Authors.name' => $name]);
    })
    ->where(function ($exp) {
        return $exp->between('written', $from, $to);
    })
    ->order(['written' => 'ASC']);

$this->set('articles', $this->paginate($query));
```

Great, but to provide additional features, you'll have to manually handle routes and/or parameters per endpoint. Though it can be needed in some cases for security reasons, most of the time it will be great to be able to refine a query from client side. With this plugin, you can simply do this :

```php
// In your index method of your ArticlesController endpoint
// URL may be /api/v1/articles?q=%7B%22innerJoinWith%22%3A%7B%22Authors%22%3A%7B%22where%22%3A%7B%22Authors.name%22%3A%22Smith%22%7D%7D%7D%2C%22where%22%3A%7B%22between%22%3A%5B%22written%22%2C%222020-01-01%22%2C%222020-12-31%22%5D%7D%2C%22order%22%3A%7B%22written%22%3A%22ASC%22%7D%7D
$query = $this->Api
    ->use($this->Articles)
    ->allow(['Authors']) // This is needed to allow access to Authors model from Articles endpoint side
    ->find();

$this->set('articles', $this->paginate($query));
```

The `q` query parameter when url and json decoded turns into this PHP array which is the query descriptor :

```php
[
    'innerJoinWith' => ['Authors', "()" => ['where' => [['Author.name' => 'Smith']]]],
    'where' => ["()" => ['between' => ['written','2020-01-01','2020-12-31']]],
    'order' => [['written' => 'ASC']],
]
```

It might be more comprehensive on JSON format :

```json
{
  "innerJoinWith": {
    "0": "Authors",
    "()": {
      "where": [{ "Author.name": "Smith" }]
    }
  },
  "where": {
    "()": {
      "between": ["written", "2020-01-01", "2020-12-31"]
    }
  },
  "order": [{ "written": "ASC" }]
}
```

Relying on query builder ability to chain and provide callbacks, you can remotely configure a wide range of advanced queries this way. See below for more informations about the building of a usable [query descriptor](#query-descriptor-syntax). See CakePHP [query builder](https://book.cakephp.org/4/en/orm/query-builder.html) for more informations about available features.

## Installation

Plugin is available through composer :

```bash
composer require liqueurdetoile/cakephp-apie
```

There's only a component in this plugin, therefore you're not required to explicitly add plugin at bootstrap step.

In your controller(s) that will use the component, simply load it during `initialize` hook :

```php
// In your controller
public function initialize(): void
{
    parent::initialize();
    $this->loadComponent('Lqdt/CakephpApie.Api');
}

// And use it through Api method
public function index() {
    $articles = $this->Api
        ->use('Articles')
        ->find()
        ->all()

    // ...
}
```

## Component options

When initializing component, you can permanently alter the used query parameters name by submitting mapped default ones :

```php
// In your controller
$this->loadComponent('Lqdt/CakephpApie.Api', [
    'q' => 'whatever', // Permanently remap the query parameter monitored in URL to this value
    'allowAll' => true, // Allow any associated data to be requested. Not recommended unless you know what you're doing
]);
```

With this config, component will now look into `whatever` query parameter to find a query descriptor and will not check for allowance when associated data is requested.

## Component methods

`ApiComponent::use(\Cake\ORM\Table\|string $model) : self`

Instruct component to use given table as base for the request. You can either provide a `Table` instance or its name in `TableRegistry`.

`ApiComponent::setQueryParam(string $name) : self`

Component will try to find a query descriptor from the `$name` key in query string instead of the default `q` key.

`ApiComponent::allow(string|string[] $associations) : self`

Configure component to allow the use of given associations by their names. Dotted paths are allowed.

> **TIP** : Allowing a nested association automatically allows all intermediate associations in the path

`ApiComponent::allowAll() : self`

Disable association check for the current request.

> **TIP** : Allowing any associated data to be fetched can be a very bad idea in most cases

`ApiComponent::find() : \Cake\ORM\Query`

Returns a query which have been initialized based on descriptor provided in url. It accepts the same parameters than any regular `find` call.

`ApiComponent::configure(\Cake\ORM\Query $query, array $descriptor) : \Cake\ORM\Query`

Returns a query object which clauses have been initialized based on descriptor.

> **TIP** You can use this feature if you're not planning to use url query parameter as descriptor source

## Query descriptor syntax

A query descriptor is an array describing how to configure a query. Any available callable on a query can be used.

A very basic descriptor will look like :

```php
[
    "query_method1" => [/** arguments */]
    "query_method2" => [/** arguments */]
]

// This will be used this way :
call_user_func_array([$query, "query-method1"], [/** arguments */];
call_user_func_array([$query, "query-method2"], [/** arguments */];
```

Plugin expects that provided arguments are directly usable for `call_user_func_array`, therefore they must be wrapped into an array.

If you need to call the same method multiple times, simply add `+` as needed. They will be trimmed during parsing :

```php
[
    "query_method" => [/** arguments */]
    "+query_method" => [/** arguments */]
    "++query_method" => [/** arguments */]
]

// This will be used this way :
call_user_func_array([$query, "query-method"], [/** arguments */];
call_user_func_array([$query, "query-method"], [/** arguments */];
call_user_func_array([$query, "query-method"], [/** arguments */];
```

### Query expressions

You can tell plugin that you're willing to use a query expression by using `newExpr()` special key :

```php
[
    'where' => [
        'newExpr()' => [
            'between' => ['date', '2020-01-01', '2020-12-31']
        ]
    ]
];

// turns into
call_user_func([$query, 'where'], [
    call_user_func([$query->newExpr(), 'between'], ['date', '2020-01-01', '2020-12-31'])
]);
```

### SQL functions

You can also use SQL functions with `func()` special key :

```php
[
    'select' => [
        ['count' => [
            'func()' => [
                'count' => ['*'],
            ],
        ]],
    ],
];

// turns into
call_user_func([$query, 'select'], [
    call_user_func([$query->func(), 'count'], ['*'])
]);
```

### Closures

Finally, you can tell plugin that you want to use a closure by using `()` special key. Except for associations closure, a query expression will be available and used to process nested descriptor.

```php
[
    'where' => [
        '()' => [
            'between' => ['date', '2020-01-01', '2020-12-31']
        ]
    ]
];

// turns into
call_user_func([$query, 'where', [
    function (QueryExpression, $e) {
        call_user_func([$e, 'between'], ['date', '2020-01-01', '2020-12-31']);

        return $e;
    }
]]);

```

For associations, plugin will apply nested descriptor to the subquery in the closure :

```php
[
    'contain' => [
        'Childs',
        '()' => [
            'where' => [['Childs.is_great' => true]]
        ]
    ]
];

// turns into
call_user_func([$query, 'contain', [
    'Childs',
    function (Query $q) {
        call_user_func([$q, 'where'], [['Childs.is_great' => true]]);

        return $q;
    }
]]);

```
