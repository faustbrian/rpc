<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Repositories;

use Cline\RPC\Contracts\MethodInterface;
use Cline\RPC\Exceptions\MethodAlreadyRegisteredException;
use Cline\RPC\Exceptions\MethodNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

use function is_string;
use function throw_if;

/**
 * Registry for JSON-RPC method implementations.
 *
 * Manages the collection of available JSON-RPC methods, providing registration,
 * retrieval, and lookup capabilities. Methods are indexed by their name for
 * fast access during request routing.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class MethodRepository
{
    /**
     * Registered method instances, indexed by method name.
     *
     * @var array<string, MethodInterface>
     */
    private array $methods = [];

    /**
     * Creates a new method repository and registers the provided methods.
     *
     * @param array<int, MethodInterface|string> $methods Initial methods to register
     */
    public function __construct(array $methods = [])
    {
        foreach ($methods as $method) {
            $this->register($method);
        }
    }

    /**
     * Returns all registered methods.
     *
     * @return array<string, MethodInterface> Array of method instances indexed by name
     */
    public function all(): array
    {
        return $this->methods;
    }

    /**
     * Retrieves a method by its name.
     *
     * @param string $method Method name to lookup (e.g., 'user.create')
     *
     * @throws MethodNotFoundException When the requested method is not registered
     *
     * @return MethodInterface Registered method instance
     */
    public function get(string $method): MethodInterface
    {
        $method = $this->methods[$method] ?? null;

        throw_if($method === null, MethodNotFoundException::create());

        return $method;
    }

    /**
     * Registers a new method in the repository.
     *
     * Accepts either a method instance or a class name that will be resolved
     * from the container. Prevents duplicate registration of the same method name.
     *
     * @param MethodInterface|string $method Method class name or instance to register
     *
     * @throws MethodAlreadyRegisteredException When attempting to register a method name that already exists
     */
    public function register(string|MethodInterface $method): void
    {
        if (is_string($method)) {
            /** @var MethodInterface $method */
            $method = App::make($method);
        }

        $methodName = $method->getName();

        throw_if(Arr::has($this->methods, $methodName), MethodAlreadyRegisteredException::forMethod($methodName));

        $this->methods[$methodName] = $method;
    }
}
