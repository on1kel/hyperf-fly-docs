# Khazhinov Hyperf FlyDocs

**Khazhinov Hyperf FlyDocs** — расширение для [Hyperf](https://hyperf.io), которое автоматически формирует и публикует OpenAPI (Swagger) документацию на основе кода и фабрик (`ComplexFactoryInterface`).

Вместо ручного описания аннотаций вы просто указываете `#[Complex(...)]`, а FlyDocs собирает полную спецификацию с параметрами, схемами и ответами.

---

## 🚀 Возможности

- Генерация OpenAPI схем из PHP-атрибутов  
- Поддержка «комплексов» — декларативных описаний операций (`ComplexFactoryInterface`)  
- Автоматическая публикация Swagger UI  
- Кеширование спецификаций через `DocsCacheManager`  
- Поддержка коллекций (`{tag}`) для разных версий документации  
- Интеграция с `On1kel\OAS\Builder`

---

## 📦 Установка

```bash
composer require khazhinov/hyperf-flydocs
```

Пакет автоматически зарегистрируется через `ConfigProvider`.

---

## ⚙️ Публикация ресурсов

```bash
php bin/hyperf.php vendor:publish khazhinov/hyperf-flydocs
```

Будут созданы:

| ID | Назначение | Путь |
|----|-------------|------|
| `config` | Конфиг FlyDocs | `config/autoload/fly-docs.php` |
| `ui` | Swagger UI файлы | `publish/fly-docs` |

Если конфиг отсутствует — он создаётся автоматически при первом запуске.

---

## 🌍 Маршруты документации

| Метод | Путь | Назначение |
|--------|------|------------|
| `GET` | `/fly-docs` | Редирект на коллекцию по умолчанию |
| `GET` | `/fly-docs/{tag}` | Swagger UI для выбранной коллекции |
| `GET` | `/fly-docs/{tag}/api-docs` | JSON спецификация OpenAPI |
| `GET` | `/fly-docs-assets/{path}` | Раздача статических файлов UI |

Swagger UI доступен по адресу:

```
http://{host}:{?port}/fly-docs
```

---

## 🔧 Конфигурация (`config/autoload/fly-docs.php`)

```php
return [
    'default_collection' => 'latest',

    'cache' => [
        'enabled' => true,
        'path' => BASE_PATH . '/runtime/flydocs',
    ],

    'ui' => [
        'index_title' => 'API Documentation',
        'use_cdn' => false, // false = использовать локальные файлы из publish/fly-docs
    ],
];
```

---

## ✍️ Как документировать контроллеры

FlyDocs анализирует контроллеры Hyperf и их атрибуты.  
Самый важный атрибут — `#[Complex(...)]`, который указывает фабрику для сборки OpenAPI-описания.

### Пример контроллера

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Khazhinov\HyperfFlyDocs\Attributes\Operation;
use Khazhinov\HyperfFlyDocs\Attributes\Complex;
use App\Models\Comment;
use App\Http\Resources\CommentCollection;
use Khazhinov\HyperfLighty\OpenApi\Complexes\IndexActionComplex;

#[Controller(prefix: 'api/comments')]
final class CommentController
{
    #[PostMapping(path: 'search')]
    #[Operation(tags: ['Comment'])]
    #[Complex(
        factory: IndexActionComplex::class,
        model_class: Comment::class,
        collection_resource: CommentCollection::class,
        options: []
    )]
    public function search()
    {
        // обычная бизнес-логика
    }
}
```

Этот код сообщает FlyDocs:
- использовать фабрику `IndexActionComplex`,
- сгенерировать операцию `POST /api/comments/search`,
- применить модель `Comment` и ресурс коллекции `CommentCollection`,
- добавить все фильтры, пагинацию, экспорт и ответы, определённые фабрикой.

---

## 🧩 Что делает Complex-фабрика

`ComplexFactoryInterface` позволяет описать **шаблон операции**, который FlyDocs потом применяет к каждому методу.

Пример — `IndexActionComplex` (входит в пакет `HyperfLighty`):

```php
final class IndexActionComplex implements ComplexFactoryInterface
{
    public function build(...$arguments): ComplexResultDTO
    {
        // 1. Сбор схем через ModelReflector
        // 2. Построение query-параметров (pagination, orders)
        // 3. Формирование RequestBody с фильтрацией
        // 4. Добавление успешных и ошибочных ответов
        // 5. Возврат ComplexResultDTO с готовыми описаниями
    }
}
```

Результат работы фабрики — `ComplexResultDTO`, содержащий:
- `request_body` (`RequestBody`),
- `parameters` (`Parameter[]`),
- `responses` (`ResponsesBuilder`).

---

## 🔄 Генерация и кеш

При старте воркера (`GenerateDocsOnWorkerStartListener`) FlyDocs:

1. Извлекает маршруты (`CollectorRouteExtractor`).
2. Применяет фильтры (`RouteFilter`).
3. Строит операции через фабрики (`ComplexRunner`, `OperationComposer`, `OperationMetaResolver`).
4. Сохраняет итоговую спецификацию в кеш (`DocsCacheManager`).
5. Раздаёт JSON и UI через `DocsController`.

---

## 🖥 Swagger UI

Swagger UI поставляется вместе с пакетом (`publish/ui/`).  
Если UI опубликован — используется локальный вариант.  
Если нет — контроллер автоматически подставит CDN-версию.

Путь по умолчанию:  
```
/fly-docs
```

---

## 📄 Лицензия

Распространяется под лицензией **MIT**  

---

## 💡 Полезно знать

- Весь код генерации располагается в пространстве имён `Khazhinov\HyperfFlyDocs\Generator`.  
- Все классы, реализующие `ComplexFactoryInterface`, могут быть использованы как фабрики для `#[Complex(...)]`.  
- Swagger UI можно переопределить или подключить собственный шаблон, разместив файл `publish/fly-docs/index.html`.

---

**Khazhinov Hyperf FlyDocs** — декларативный способ документировать Hyperf-приложения без ручного редактирования OpenAPI.
