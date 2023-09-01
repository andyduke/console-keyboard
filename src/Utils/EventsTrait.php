<?php

namespace AndyDuke\ConsoleKeyboard\Utils;

trait EventsTrait
{
    /**
     * The registered event listeners.
     *
     * @var array<string, array<int, Closure>>
     */
    protected array $listeners = [];

    /**
     * Register an event listener.
     */
    public function addListener(string $event, Closure $callback): void
    {
        $this->listeners[$event][] = $callback;
    }

    /**
     * Emit an event.
     */
    public function fireEvent(string $event, mixed ...$data): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(...$data);
        }
    }
}
