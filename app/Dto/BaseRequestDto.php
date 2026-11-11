<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Dto;

use Hyperf\Context\Context;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Validation\Request\FormRequest;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

use function Hyperf\Collection\data_get;

/**
 * 请求 DTO 基类 —— 承担「请求体解析」和「参数校验」两个职责.
 *
 * 每个模块只需一个 XxxRequest 类，用 getRouteAction() 区分 create/update/list 等场景，
 * 不按方法拆分多个类。
 *
 * 基类已内置分页、搜索、排序等高频场景的参数与规则，子类只需声明自己的业务字段。
 *
 * 约定：
 * - 子类用 public 属性声明字段，全部给默认值 null
 * - 子类实现 rules()，用 $this->getRouteAction() 或 $this->isListAction() 分支
 * - 校验通过后 hydrate() 自动写入属性并做类型转换
 *
 * 数据落在协程上下文而不是实例属性上：DTO 由容器单例持有，同一个 worker 会
 * 反复复用同一个对象，实例属性会把上一个请求的值带进下一个请求（并发协程
 * 之间还会互相覆盖）。声明属性在构造时被摘掉，读写统一走 __get/__set。
 */
abstract class BaseRequestDto extends FormRequest
{
    public ?int $page = null;

    public ?int $limit = null;

    public ?string $search = null;

    /**
     * 排序：['field' => 'id', 'direction' => 'desc'].
     */
    public ?array $sort = null;

    /**
     * 列表详情模式：传 detail 时返回全字段并预加载关联.
     */
    public ?string $with = null;

    /**
     * 重写容器构造：实例由容器单例复用，属性数据不能留在对象身上.
     *
     * 摘掉声明属性后，读写才会落到 __get/__set，转而存进协程上下文。
     * 注意只对容器单例这么做：fromArray() 新建的对象存活期不超过一次调用，
     * 实例属性天然按对象隔离，改存上下文反而会让同请求内的多个实例互相覆盖。
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        foreach ($this->hydratablePropertyNames() as $name) {
            unset($this->{$name});
        }
    }

    public function __get($name): mixed
    {
        return Context::get($this->contextKey($name));
    }

    public function __set($name, $value): void
    {
        Context::set($this->contextKey($name), $value);
    }

    public function __isset($name): bool
    {
        return Context::has($this->contextKey($name));
    }

    public function __unset($name): void
    {
        Context::destroy($this->contextKey($name));
    }

    /**
     * 授权检查：认证与权限由中间件负责，这里统一放行.
     *
     * 必须显式声明，否则 ValidationMiddleware 调 passesAuthorization() 时
     * 会命中父类/魔术方法返回 false，直接返回空的 403。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 分页页码，默认 1.
     */
    public function pageNumber(): int
    {
        return max(1, (int) ($this->page ?? 1));
    }

    /**
     * 每页条数，默认 10，上限 100.
     */
    public function pageSize(): int
    {
        $limit = (int) ($this->limit ?? 10);

        return $limit < 1 ? 10 : min($limit, 100);
    }

    /**
     * 当前动作是否为列表查询.
     */
    public function isListAction(): bool
    {
        return in_array($this->getRouteAction(), ['list', 'index', 'records'], true);
    }

    /**
     * 校验通过后自动把值写入同名属性.
     */
    public function validateResolved(): void
    {
        parent::validateResolved();
        $this->hydrate();
    }

    /**
     * 把校验后的数据写入同名字段属性，并按属性类型做转换.
     *
     * 查询串里的值都是字符串（如 ?limit=2），必须转成属性声明的类型，
     * 否则给 ?int 属性赋值会抛 TypeError。
     */
    public function hydrate(): static
    {
        $data = $this->validated();
        $ref = new ReflectionClass($this);

        foreach ($this->hydratablePropertyNames() as $name) {
            unset($this->{$name});

            $value = data_get($data, $name, null);
            if ($value === null) {
                continue;
            }

            $this->{$name} = $this->castValue($value, $ref, $name);
        }

        return $this;
    }

    /**
     * 脱离 HTTP 上下文构造 DTO，供单元测试与批量场景使用.
     *
     * 新建对象的属性直接留在实例上，不写协程上下文：批量场景一次会构造多个
     * 同类型实例，它们必须各自持有自己的数据。
     */
    public static function fromArray(array $data): static
    {
        /** @var static $dto */
        $dto = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();
        $dto->fillFrom($data);

        return $dto;
    }

    /**
     * 只输出本 DTO 声明的业务字段.
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->dtoPropertyNames() as $name) {
            $result[$name] = $this->{$name};
        }
        return $result;
    }

    /**
     * 业务字段 + 通用查询参数（page/limit/search/sort），供列表类 Action 使用.
     */
    public function toQueryArray(): array
    {
        return array_filter(
            array_merge($this->toArray(), [
                'page' => $this->page,
                'limit' => $this->limit,
                'search' => $this->search,
                'sort' => $this->sort,
            ]),
            static fn ($v) => $v !== null
        );
    }

    /**
     * 列表是否需要全字段与关联数据.
     */
    public function withDetail(): bool
    {
        return $this->with === 'detail';
    }

    /**
     * 转成可直接写库的数组（已剔除虚拟字段）.
     */
    public function toDbArray(): array
    {
        return array_diff_key($this->toArray(), array_flip($this->virtualFields()));
    }

    /**
     * 转成可直接写库的数组，过滤掉 null（用于 update 场景）.
     */
    public function toDbArrayFiltered(): array
    {
        return array_filter($this->toDbArray(), static fn ($v) => $v !== null);
    }

    /**
     * 通用列表参数规则（page/limit/search/sort）.
     */
    protected function paginationRules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:100',
            'sort' => 'nullable|array',
            'sort.field' => 'nullable|string|max:50',
            'sort.direction' => 'nullable|in:asc,desc',
            'with' => 'nullable|string|in:detail',
        ];
    }

    /**
     * 校验数据 = 路由参数 + 请求参数（请求参数优先）.
     *
     * update/delete 这类场景的主键在路由里，不并进来 required 规则永远无法通过。
     */
    protected function validationData(): array
    {
        $params = [];
        $dispatched = $this->getAttribute(Dispatched::class);
        if ($dispatched && is_array($dispatched->params)) {
            $params = $dispatched->params;
        }

        return array_merge($params, parent::validationData());
    }

    /**
     * 按属性声明的类型转换值.
     */
    protected function castValue(mixed $value, ReflectionClass $ref, string $name): mixed
    {
        if (! $ref->hasProperty($name)) {
            return $value;
        }

        $type = $ref->getProperty($name)->getType();
        if (! $type instanceof ReflectionNamedType) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => is_array($value) ? $value : (array) $value,
            default => $value,
        };
    }

    /**
     * 需要回填的属性名：业务字段 + 基类的通用查询参数.
     *
     * page/limit/search/sort 虽然属于基类，但同样是请求参数，必须一并回填，
     * 否则 toQueryArray() 永远丢不出分页条件。
     * @return array<int, string>
     */
    protected function hydratablePropertyNames(): array
    {
        return [...$this->dtoPropertyNames(), 'page', 'limit', 'search', 'sort', 'with'];
    }

    /**
     * 取子类声明的业务字段名（不含基类的 page/limit/search/sort）.
     * @return array<int, string>
     */
    protected function dtoPropertyNames(): array
    {
        $names = [];
        $ref = new ReflectionClass($this);

        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $declaring = $prop->getDeclaringClass()->getName();
            // 基类自身的 page/limit/search/sort 属于通用查询参数，不算业务字段
            if ($declaring !== self::class && str_starts_with($declaring, 'App\Dto\\')) {
                $names[] = $prop->getName();
            }
        }

        return $names;
    }

    /**
     * 按属性名与类型回填数据.
     *
     * 这里直接写实例属性，不能走 unset：fromArray() 建的对象属性还在实例上，
     * 一旦 unset 就会落到 __set 存进协程上下文，批量场景的几个实例会互相覆盖。
     */
    protected function fillFrom(array $data): void
    {
        $ref = new ReflectionClass($this);

        foreach ($this->hydratablePropertyNames() as $name) {
            $value = data_get($data, $name, null);
            if ($value === null) {
                continue;
            }

            $this->{$name} = $this->castValue($value, $ref, $name);
        }
    }

    /**
     * 属性在协程上下文中的存储键：按 DTO 类隔离.
     */
    protected function contextKey(string $name): string
    {
        return static::class . '.dto.' . $name;
    }

    /**
     * 当前路由的控制器方法名，用于区分场景.
     */
    protected function getRouteAction(): string
    {
        $dispatched = $this->getAttribute(Dispatched::class);
        if (! $dispatched || ! $dispatched->handler) {
            return '';
        }

        $callback = $dispatched->handler->callback;
        if (is_string($callback) && str_contains($callback, '@')) {
            return explode('@', $callback)[1] ?? '';
        }
        if (is_array($callback) && isset($callback[1])) {
            return $callback[1];
        }

        return '';
    }

    /**
     * 只用于业务、不写库的属性名，子类按需覆盖.
     *
     * 如 role_ids、menu_ids 这类关联 ID 集合。
     * @return array<int, string>
     */
    protected function virtualFields(): array
    {
        // 主键由路由/业务层单独处理，从不作为可写字段
        return ['id'];
    }
}
