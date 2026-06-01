<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Support;

/**
 * Testbench/Laravel installs its own error handler which intercepts
 * deprecations during the booted application (e.g. while dispatching HTTP
 * requests) and routes them to its log channel _without_ forwarding them to
 * the error handler registered underneath (PHPUnit's). As a result PHPUnit
 * never sees those deprecations and cannot report/classify them.
 *
 * This handler is installed on top of the stack (the booted kernel does not
 * re-register handlers mid-request, see Illuminate\Foundation\Http\Kernel::bootstrap)
 * and forwards deprecations to the innermost handler (PHPUnit's), while
 * delegating every other error to the handler it replaced so non-deprecation
 * behaviour is unchanged.
 *
 * Both handlers are kept as plain callables obtained from the error handler
 * stack via {@see set_error_handler()}; we deliberately do not reference
 * PHPUnit's `@internal` error handler class.
 *
 * `handle()` is registered as a `<deprecationTrigger>` in phpunit.xml.dist so
 * PHPUnit strips this frame when determining a deprecation's origin. It is a
 * static method on a dedicated class on purpose: the resulting stack frame then
 * has a stable `class` (this class) which `<deprecationTrigger>` can match,
 * unlike an instance method on the (subclassed) TestCase.
 */
class DeprecationForwarder
{
    /**
     * The handler our handler was installed on top of (Testbench/Laravel's);
     * non-deprecations are delegated to it.
     *
     * @var callable|null
     */
    private static $previous;

    /**
     * The innermost handler on the stack (PHPUnit's); deprecations are
     * forwarded to it so PHPUnit captures, classifies and reports them.
     *
     * @var callable|null
     */
    private static $innermost;

    public static function register(): void
    {
        $stack = self::popStack();

        self::$previous = $stack[0] ?? null;
        self::$innermost = [] !== $stack ? $stack[\count($stack) - 1] : null;

        // Restore the original stack (bottom first) ...
        foreach (array_reverse($stack) as $handler) {
            set_error_handler($handler);
        }

        // ... and install ourselves on top.
        set_error_handler([self::class, 'handle']);
    }

    public static function unregister(): void
    {
        restore_error_handler();

        self::$previous = null;
        self::$innermost = null;
    }

    public static function handle(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (E_DEPRECATED === $errno || E_USER_DEPRECATED === $errno) {
            if (null !== self::$innermost) {
                (self::$innermost)($errno, $errstr, $errfile, $errline);
            }

            // Handled: do not let Laravel log it nor PHP print/throw it (which
            // would otherwise mark the test as risky via output).
            return true;
        }

        if (null !== self::$previous) {
            return (bool) (self::$previous)($errno, $errstr, $errfile, $errline);
        }

        return false;
    }

    /**
     * Removes and returns the current error handler stack, top first.
     *
     * @return list<callable>
     */
    private static function popStack(): array
    {
        $stack = [];

        while (true) {
            // Peek the current top handler by pushing+popping a throwaway one.
            $current = set_error_handler(static fn (): bool => false);
            restore_error_handler();

            if (null === $current) {
                break;
            }

            $stack[] = $current;
            restore_error_handler();
        }

        return $stack;
    }
}
