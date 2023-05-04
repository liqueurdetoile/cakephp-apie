# Cake as a Pie

**This is a CakePHP plugin for 4.x branch.**

The main idea of this plugin is to :

- remotely leverage power of CakePHP query builder to configure advanced data queries through inbound URL parameters
- provides a quick way to define which fields and/or associated models will be accessible for CRUD operations.

Except for pagination, CakePHP framework is lacking tools to quicky achieve these goals. The plugin by itself is fairly agnostic about endpoints routing logic.

- [Cake as a Pie](#cake-as-a-pie)
  - [Quick overview](#quick-overview)
  - [Reference](#reference)
    - [Used query parameter](#used-query-parameter)
    - [Component options](#component-options)
    - [Component methods](#component-methods)
  - [Query descriptor syntax](#query-descriptor-syntax)

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

That's great, but you must manually handle routes and/or parameters to add more features to your api. Though it can be needed in some cases for security reasons, most of the time it will be great to be able to refine a query from client side. With this plugin, you can simply do this :

```php
// In your index method of your ArticlesController endpoint
// URL may be /api/v1/articles?q=%7B%22innerJoinWith%22%3A%7B%22Authors%22%3A%7B%22where%22%3A%7B%22Authors.name%22%3A%22Smith%22%7D%7D%7D%2C%22where%22%3A%7B%22between%22%3A%5B%22written%22%2C%222020-01-01%22%2C%222020-12-31%22%5D%7D%2C%22order%22%3A%7B%22written%22%3A%22ASC%22%7D%7D
$query = $this->Api
    ->use($this->Articles)
    ->allow(['Authors']) // This is needed to allow access to Authors model from Articles endpoint side
    ->find();

$this->set('articles', $this->paginate($query));
```

The `q` query parameter when url and json decoded turns into this PHP array :

```php
[
    'innerJoinWith' => ['Authors', () => ['where' => [['Author.name' => 'Smith']]]],
    'where' => ['between' => ['written','2020-01-01','2020-12-31']],
    'order' => [['written' => 'ASC']],
]
```

Relying on query builder ability to chain and provide callbacks, you can write mostly any advanced query this way. Logic is quite simple : when an associative array is found, plugin will try to match key to an available method in the current executive context (query, query expression or function builder) and use the value as argument in a `call_user_func_array` call.

> **TIP** Any array as single argument must have double brackets to avoid parsing issues

> **TIP** Due to array key limitations, the descriptor syntax does not support multiple calls of the same command

See below for more informations about the building of a usable [query descriptor](#query-descriptor-syntax).

See CakePHP [query builder](https://book.cakephp.org/4/en/orm/query-builder.html) for more informations about available features.

## Reference

### Used query parameter

| Default parameter name | Expected encoding | Setter                        | Description                                    |
| :--------------------: | :---------------: | ----------------------------- | ---------------------------------------------- |
|          `q`           |      `JSON`       | `ApiComponent::setQueryParam` | Contains the directives for the query builder. |

Parameter`s name can be changed when initializing component (see below) or for a specific request through its setter.

### Component options

When initializing componenent, you can permanently alter the used query parameters name by submitting mapped default ones :

```php
    // In your controller
    $this->loadComponent('Lqdt/CakephpApie.Api', [
        'q' => 'whatever',
    ]);
```

Component will now look into `whatever` query parameter to find a query descriptor.

### Component methods

`ApiComponent::use(\Cake\ORM\Table\|string $model) : self`

Instruct component to use given table as base for the request. You can either provide a `Table` instance or its name in `TableRegistry`.

`ApiComponent::allow(string|string[] $associations) : self`

Configure component to allow the use of given associations by their names. Dotted paths are allowed.

> **TIP** : Allowing a nested association automatically allows all intermediate associations in the path

`ApiComponent::find() : \Cake\ORM\Query`

Returns a query object which clauses have been initialized based on url parameter. It accepts the same parameters than any regular `find` call.

`ApiComponent::configure(\Cake\ORM\Query $query, array $descriptor) : \Cake\ORM\Query`

Returns a query object which clauses have been initialized based on url parameter. It accepts the same parameters than regular `find` call.

## Query descriptor syntax

A query descriptor is an array describing how to configure a query. Any available callable on a query can be used.

```php
// This is extracted in query parameter by the component
$descriptor = [
    'where' => [['is_great' => true]],  // Notice double brackets use
];

$descriptor = [
    'where' => ['is_great' => true],
];

// It will be used to process query like so
$query = $Table->find();
call_user_func_array([$query, 'where', [['is_great' => true]]);
// That is analog to
$query->where(['is_great' => true]);
```

You can also pass nested parameter(s) :

```php
// This is extracted in query parameter by the component
$descriptor = [
    'contain' => [[ // Notice double brackets use
            'Childs' => [
                'sort' => [
                    'Childs.created' => 'DESC'
                ]
            ]
    ]],
];

// It will be used to process query like so
$query = $Table->find();
call_user_func_array([$query, 'contain', [['Childs' => ['sort' => ['Childs.created' => 'DESC']]]]);
```

> **TIP** When the component finds an indexed array, it will use `call_user_func_array` on current context and pass the array. Therefore, do not forget to enclose your array-like parameters into brackets, otherwise the component will try (in that case) to find a `Childs` method on the query object and will raise an error.

You may also want/have to use callback closure for more advanced query setup. Simply use a key named `()` to enter callback mode :

```php
// This time we'll start on the expected result
$query = $Table->find()->contain('Childs', function($q) {
    return $q->order(['Childs.created' => 'DESC']);
})

// The descriptor for this query will be
$descriptor = [
    'contain' => [
        'Childs',
        '()' => [
            'order' => [['Childs.created' => 'DESC']],  // Notice double brackets use
        ]
    ],
];
```

In a closure, the component will first try to apply next descriptor nodes to QueryExpression (if one available) and fallback on Query provided by local scope.

Lastly, the `func()` and `newExpr()` keys will also change local scope to, respectively, function builder and query expression. Let's see in action from an advanced pattern found on CakePHP cookbook :

```php
$query = $articles->find()->where(function (QueryExpression $exp, Query $query) {
    // Use add() to add multiple conditions for the same field.
    $author = $query->newExpr()->or(['author_id' => 3])->add(['author_id' => 2]);
    $published = $query->newExpr()->and(['published' => true, 'view_count' => 10]);

    return $exp->or([
        'promoted' => true,
        $query->newExpr()->and([$author, $published])
    ]);
});

// the descriptor for this one will be :
$descriptor = [
    'where' => [
        '()' => [
            'or' => [ // This is available on scoped QueryExpression
                ['promoted' => true],
                'newExpr()' => [ // This is available on scoped Query
                    'and' => [
                        'newExpr()' => [
                            'or' => ['author_id' => 3],
                            'add' => ['author_id' => 2],
                        ],
                        'newExpr()' => [
                            'and' => [['published' => true, 'view_count' => 10]],  // Notice the double brackets here
                        ]
                    ]
                ]
            ]
        ]
    ]
];
```

```php
$query->where(['created' => true], true);

'where' => [
    ['created' => true],
    true
]

call_user_func_array($query->where, [['created' => true], true])

$query->where(function($e) {
return $e->eq('created', true);
})

'where' => [
    '()' => [
        'eq' => ['created', true];
    ]
]

$q->select(['count' => $q->func()->count('*')])

[
    'select' => [
        [
            'count' => [
                'func()' => [
                    'count' => ['*']
                ]
            ]
        ]
    ]
]

call_user_func($query->select, [
    ['count' => ]
]);
```
