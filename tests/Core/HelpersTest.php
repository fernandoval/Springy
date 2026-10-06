<?php

/**
 * Test case for the helper functions in springy/Core/helpers.php.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2026 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.0
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Springy\Core\Application;
use Springy\Core\Debug;
use Springy\Events\Mediator;
use Springy\Exceptions\HttpError;
use Springy\Exceptions\SpringyException;
use Springy\Kernel;

class HelpersTest extends TestCase
{
    private const ENV_KEY = 'SPRINGY_PHPUNIT_HELPER_ENV';
    private const IGNORED_ERROR = 987654;

    /** @var string temporary directory used by filesystem tests */
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . DS . 'springy_helpers_' . uniqid();
    }

    protected function tearDown(): void
    {
        putenv(self::ENV_KEY);
        unset($_ENV[self::ENV_KEY], $_SERVER[self::ENV_KEY], $_COOKIE['phpunit_cookie']);

        $this->removeDir($this->tmpDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . DS . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }

    public function testConstantsAreDefined()
    {
        $this->assertSame(DIRECTORY_SEPARATOR, DS);
        $this->assertSame("\n", LF);
        $this->assertSame('user.auth.driver', USER_AUTH_DRIVE);
        $this->assertSame('security.hasher', USER_AUTH_HASHER);
        $this->assertSame('user.auth.identity', USER_AUTH_IDENTITY);
        $this->assertSame('user.auth.manager', USER_AUTH_MANAGER);
    }

    public function testAppReturnsSharedApplicationInstance()
    {
        $app = app();

        $this->assertInstanceOf(Application::class, $app);
        $this->assertSame($app, app());
        $this->assertSame(Application::sharedInstance(), $app);
    }

    public function testAppReturnsRegisteredService()
    {
        $this->assertInstanceOf(Mediator::class, app('events'));
        $this->assertSame(app()['events'], app('events'));
    }

    public function testAppThrowsForUnregisteredService()
    {
        $this->expectException(InvalidArgumentException::class);

        app('phpunit.unregistered.service');
    }

    public function testApplicationInfo()
    {
        $this->assertSame(APP_CODE_NAME, app_codename());
        $this->assertSame(APP_NAME, app_name());
        $this->assertSame(APP_VERSION, app_version());
    }

    public function testDirectoryPaths()
    {
        $this->assertSame(PROJECT_ROOT, project_path());
        $this->assertSame(APP_PATH, app_path());
        $this->assertSame(CACHE_DIR, cache_dir());
        $this->assertSame(CONFIG_DIR, config_dir());
        $this->assertSame(MIGRATION_DIR, migration_dir());
        $this->assertSame(VAR_DIR, var_dir());
        $this->assertSame(WEB_ROOT, web_root());
    }

    public function testArrayDottedGet()
    {
        $array = [
            'a' => [
                'b' => [
                    'c' => 'value',
                ],
            ],
            'x' => 1,
        ];

        $this->assertSame('value', array_dotted_get($array, 'a.b.c'));
        $this->assertSame(['c' => 'value'], array_dotted_get($array, 'a.b'));
        $this->assertSame(1, array_dotted_get($array, 'x'));
        $this->assertNull(array_dotted_get($array, 'a.b.z'));
        $this->assertSame('default', array_dotted_get($array, 'a.z.c', 'default'));
    }

    public function testArrayDottedSet()
    {
        $array = ['a' => ['b' => 1]];

        array_dotted_set($array, 'a.c', 2);
        array_dotted_set($array, 'x.y.z', 'deep');
        array_dotted_set($array, 'a.b', 10);

        $this->assertSame(
            [
                'a' => ['b' => 10, 'c' => 2],
                'x' => ['y' => ['z' => 'deep']],
            ],
            $array
        );
    }

    public function testBuildUrl()
    {
        $this->assertSame(
            'https://example.com/a-b/c?x=1+2&y[k]=v',
            build_url(['a b', 'index', 'c'], ['x' => '1 2', 'y' => ['k' => 'v']], 'https://example.com')
        );
        $this->assertSame('https://example.com/', build_url([], [], 'https://example.com'));
    }

    public function testConfigSetAndGet()
    {
        config_set('phpunit_helpers.key', 'value');
        config_set('phpunit_helpers.nested.key', ['a' => 1]);

        $this->assertSame('value', config_get('phpunit_helpers.key'));
        $this->assertSame(['a' => 1], config_get('phpunit_helpers.nested.key'));
        $this->assertSame(1, config_get('phpunit_helpers.nested.key.a'));
        $this->assertNull(config_get('phpunit_helpers.missing'));
        $this->assertSame('default', config_get('phpunit_helpers.missing', 'default'));
    }

    public function testCookieGet()
    {
        $_COOKIE['phpunit_cookie'] = 'cookie value';

        $this->assertSame('cookie value', cookie_get('phpunit_cookie'));
        $this->assertNull(cookie_get('phpunit_cookie_missing'));
    }

    public function testDdWithoutDie()
    {
        ob_start();
        var_dump(['a' => 1]);
        $dump = ob_get_clean();

        $this->expectOutputString('<pre>' . $dump . '</pre>');

        dd(['a' => 1], false);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testDebug()
    {
        $this->assertSame('', Debug::get());

        debug('PHPUnit helper debug text', 'helper');

        $output = Debug::get();

        $this->assertStringContainsString('PHPUnit helper debug text', $output);
        // Named entries include the backtrace with the caller's file
        $this->assertStringContainsString('Backtrace (last helper)', $output);
        $this->assertStringContainsString(__FILE__, $output);
    }

    public function testCharset()
    {
        $this->assertSame('UTF-8', charset());

        $_ENV['CHARSET'] = 'ISO-8859-1';

        try {
            $this->assertSame('ISO-8859-1', charset());
        } finally {
            unset($_ENV['CHARSET']);
        }
    }

    public function testEnvReturnsDefaultWhenUndefined()
    {
        $this->assertNull(env(self::ENV_KEY));
        $this->assertSame('default', env(self::ENV_KEY, 'default'));
    }

    public function testEnvReadsFromGetenv()
    {
        putenv(self::ENV_KEY . '=from getenv');

        $this->assertSame('from getenv', env(self::ENV_KEY));
    }

    public function testEnvReadsFromServer()
    {
        $_SERVER[self::ENV_KEY] = 'from server';

        $this->assertSame('from server', env(self::ENV_KEY));
    }

    public function testEnvPrecedence()
    {
        putenv(self::ENV_KEY . '=from getenv');
        $_SERVER[self::ENV_KEY] = 'from server';
        $_ENV[self::ENV_KEY] = 'from env';

        $this->assertSame('from env', env(self::ENV_KEY));

        unset($_ENV[self::ENV_KEY]);
        $this->assertSame('from server', env(self::ENV_KEY));
    }

    public static function envQuotesProvider(): array
    {
        return [
            'double quoted' => ['"quoted value"', 'quoted value'],
            'empty quotes' => ['""', ''],
            'single double quote' => ['"', '"'],
            'quote only at start' => ['"value', '"value'],
            'single quoted' => ["'value'", "'value'"],
            'plain' => ['value', 'value'],
            'empty' => ['', ''],
        ];
    }

    #[DataProvider('envQuotesProvider')]
    public function testEnvStripsDoubleQuotes(string $value, string $expected)
    {
        $_ENV[self::ENV_KEY] = $value;

        $this->assertSame($expected, env(self::ENV_KEY));
    }

    public function testSysconfIsAliasOfEnv()
    {
        $this->assertNull(sysconf(self::ENV_KEY));

        $_ENV[self::ENV_KEY] = '"sysconf value"';
        $this->assertSame('sysconf value', sysconf(self::ENV_KEY));
    }

    public static function makeSlugProvider(): array
    {
        return [
            'accents and punctuation' => ['  Olá Mundo!  Teste ', '-', '', true, 'ola-mundo-teste'],
            'custom separator' => ['Olá Mundo', '_', '', true, 'ola_mundo'],
            'keep case' => ['Olá Mundo', '-', '', false, 'Ola-Mundo'],
            'accepted chars' => ['file name.txt', '-', '\.', true, 'file-name.txt'],
            'collapse separators' => ['a - b', '-', '', true, 'a-b'],
        ];
    }

    #[DataProvider('makeSlugProvider')]
    public function testMakeSlug(string $text, string $separator, string $accept, bool $lowercase, string $expected)
    {
        $this->assertSame($expected, make_slug($text, $separator, $accept, $lowercase));
    }

    public static function memoryStringProvider(): array
    {
        return [
            'negative' => [-5, '-5 B'],
            'zero' => [0, '0 B'],
            'one byte' => [1, '1 B'],
            'bytes' => [1023, '1023 B'],
            'kibibyte' => [1024, '1 KiB'],
            'fraction' => [1536, '1.5 KiB'],
            'rounding' => [1024 + 5, '1 KiB'],
            'mebibyte' => [1024 ** 2, '1 MiB'],
            'gibibyte' => [(int) (2.25 * 1024 ** 3), '2.25 GiB'],
            'tebibyte' => [1024 ** 4, '1 TiB'],
            'pebibyte' => [3 * 1024 ** 5, '3 PiB'],
            'exbibyte' => [1024 ** 6, '1 EiB'],
            'max int' => [PHP_INT_MAX, '8 EiB'],
        ];
    }

    #[DataProvider('memoryStringProvider')]
    public function testMemoryString(int $memory, string $expected)
    {
        $this->assertSame($expected, memory_string($memory));
    }

    public function testMinifyCss()
    {
        mkdir($this->tmpDir);
        $source = $this->tmpDir . DS . 'style.css';
        $destiny = $this->tmpDir . DS . 'min' . DS . 'style.min.css';
        file_put_contents($source, "/* comment */\nbody {\n    color: #ff0000;\n    margin: 0;\n}\n");

        $this->assertNotFalse(minify($source, $destiny));
        $this->assertFileExists($destiny);

        $minified = file_get_contents($destiny);
        $this->assertStringNotContainsString('comment', $minified);
        $this->assertStringNotContainsString("\n", $minified);
        $this->assertStringContainsString('body{', $minified);
        $this->assertSame('0664', substr(sprintf('%o', fileperms($destiny)), -4));
    }

    public function testMinifyJs()
    {
        mkdir($this->tmpDir);
        $source = $this->tmpDir . DS . 'script.js';
        $destiny = $this->tmpDir . DS . 'script.min.js';
        file_put_contents($source, "// comment\nfunction sum(a, b) {\n    return a + b;\n}\n");

        $this->assertNotFalse(minify($source, $destiny));

        $minified = file_get_contents($destiny);
        $this->assertStringNotContainsString('comment', $minified);
        $this->assertStringContainsString('function sum(a,b)', $minified);
    }

    public function testMinifyUnknownTypeCopiesContent()
    {
        mkdir($this->tmpDir);
        $source = $this->tmpDir . DS . 'file.txt';
        $destiny = $this->tmpDir . DS . 'copy.txt';
        $content = "  some   text\n\twith spaces  \n";
        file_put_contents($source, $content);

        $this->assertSame(strlen($content), minify($source, $destiny));
        $this->assertSame($content, file_get_contents($destiny));
    }

    public function testMkdirRecursive()
    {
        $path = $this->tmpDir . DS . 'a' . DS . 'b' . DS . 'c';

        $this->assertTrue(mkdir_recursive($path . DS, 0750));
        $this->assertDirectoryExists($path);
        $this->assertSame('0750', substr(sprintf('%o', fileperms($path)), -4));
        $this->assertSame('0750', substr(sprintf('%o', fileperms($this->tmpDir)), -4));

        // Calling again on an existing path must succeed
        $this->assertTrue(mkdir_recursive($path, 0750));
    }

    public function testMkdirRecursiveWithRelativeSegments()
    {
        mkdir($this->tmpDir);
        $path = $this->tmpDir . DS . '.' . DS . 'x' . DS . '..' . DS . 'y';

        $this->assertTrue(mkdir_recursive($path, 0775));
        $this->assertDirectoryExists($this->tmpDir . DS . 'x');
        $this->assertDirectoryExists($this->tmpDir . DS . 'y');
    }

    public function testMkdirRecursiveThrowsOnEmptyPath()
    {
        $this->expectException(SpringyException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Empty path.');

        mkdir_recursive(DS, 0775);
    }

    public static function studlyCapsProvider(): array
    {
        return [
            'single word' => ['foo', 'Foo'],
            'hyphenated' => ['foo-bar', 'FooBar'],
            'underscored' => ['foo_bar', 'Foo_Bar'],
            'mixed' => ['foo_bar-baz', 'Foo_BarBaz'],
            'double hyphen' => ['foo--bar', 'Foo-Bar'],
            'trailing hyphen' => ['a-', 'A-'],
            'empty' => ['', '-'],
        ];
    }

    #[DataProvider('studlyCapsProvider')]
    public function testStudlyCaps(string $value, string $expected)
    {
        $this->assertSame($expected, studly_caps($value));
    }

    public function testThrowErrorWithDefaults()
    {
        $this->expectException(SpringyException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Internal Server Error');

        throw_error();
    }

    public function testThrowErrorWithCustomValues()
    {
        $this->expectException(SpringyException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Not Found');

        throw_error(404, 'Not Found');
    }

    public function testErrorHandlersSkipIgnoredErrors()
    {
        Kernel::addIgnoredError(self::IGNORED_ERROR);

        try {
            $this->expectOutputString('');

            springyExceptionHandler(new SpringyException('Ignored exception', self::IGNORED_ERROR));
            springyExceptionHandler(new HttpError('Ignored HTTP error', self::IGNORED_ERROR));
            springyErrorHandler(self::IGNORED_ERROR, 'Ignored error', __FILE__, __LINE__);
        } finally {
            Kernel::delIgnoredError(self::IGNORED_ERROR);
        }

        $this->assertNotContains(self::IGNORED_ERROR, Kernel::getIgnoredError());
    }

    public function testUrlReturnsHostWhenNotConfigured()
    {
        $this->assertSame('phpunit-unknown-host', url('phpunit-unknown-host'));
    }
}
