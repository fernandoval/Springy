<?php
/**
 * Framework copyright class.
 *
 * @copyright 2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.2.4
 */

namespace Springy\Core;

use Springy\Kernel;

/**
 * Framework copyright class.
 */
class Copyright
{
    /**
     * Constructor method.
     *
     * @param bool $print
     */
    public function __construct($print = true)
    {
        if ($print) {
            $this->printCopyright();
        }
    }

    /**
     * Get the file information and return an array of it.
     *
     * @param string $file
     * @param string $nameSpace
     *
     * @return array
     */
    private function getFileInfo($file, $nameSpace)
    {
        $ver = ['b' => '', 'v' => '', 'n' => ''];
        $far = file($file);

        foreach ($far as $lst) {
            if (preg_match('/\*(\s*)[\\\\|@]brief[\s|\t]{1,}(.*)((\r)*(\n))$/', $lst, $arr)) {
                $ver['b'] = trim($arr[2]);
            } elseif (preg_match('/\*([\s|\t]*)\\\\version[\s|\t]{1,}(.*)((\r)*(\n))$/', $lst, $arr)) {
                $ver['v'] = trim($arr[2]);
            } elseif (preg_match('/^(class|interface)[\s|\t]{1,}([a-zA-Z0-9_]+)(\s*)(extends)*(\s*)([a-zA-Z0-9_]*)(\s*)(\\{*)/', $lst, $arr)) {
                $ver['n'] = $arr[1] . ' ' . $nameSpace . '\\' . trim($arr[2]);
                break;
            }
        }

        return $ver;
    }

    /**
     * Get the list of classes in a directory.
     *
     * @param string $dir
     * @param string $nameSpace
     *
     * @return array
     */
    private function listClasses($dir, $nameSpace)
    {
        if (!$rdir = opendir($dir)) {
            return [];
        }

        $fver = [];
        while (($file = readdir($rdir)) !== false) {
            if (filetype($dir . $file) == 'file' && substr($file, -4) == '.php') {
                $ver = $this->getFileInfo($dir . $file, $nameSpace);
                if ($ver['n'] && $ver['v']) {
                    $fver[$ver['n']] = $ver;
                }
            } elseif (!in_array($file, ['.', '..']) && filetype($dir . $file) == 'dir') {
                $fver = array_merge($fver, $this->listClasses($dir . $file . DS, $nameSpace . '\\' . $file));
            }
        }
        ksort($fver);

        return $fver;
    }

    /**
     * Prints the framework copyright page.
     */
    public function printCopyright()
    {
        if (ob_get_contents()) {
            ob_clean();
        }

        echo '<!DOCTYPE html>' . "\n";
        echo '<html>';
        echo '<head>';
        echo '<title>Springy</title>';
        echo '<style type="text/css">';
        echo 'body { padding:20px 40px;border:0;margin:0;background-color:#0F3E06;color:#fff;font-family:arial;font-size:11px;text-align:center; }';
        echo 'a, a:link, a:active, a:visited { text-decoration:none;color:#3F92D2; }';
        echo 'a:hover { color:#0B61A4; }';
        echo '.logo { display:block;padding:0 0 5px 0;border:0;border-bottom:1px solid #fff;margin:0;height:50px; }';
        echo '.logo a { display:block;color:#fff;padding:0;border:0;margin:0;height:50px;line-height:50px;vertical-align:middle;font-size:150%;font-weight:bold; }';
        echo '.logo img { vertical-align:middle; }';
        echo '.logo span { color:#FF9900; }';
        echo '.class { color:#F9F7BD; }';
        echo '.fw { color:#A2A2A2; }';
        echo '.slash { color:#888; }';
        echo '.version { color:#62E44C; }';
        echo '.description { color:#CCC; }';
        echo 'table { padding:0;border:0;margin:0 auto;cell-padding:0; }';
        echo 'tr { padding:0;border:0;margin:0; }';
        echo 'td { padding:0 5px 0 0;border:0;text-align:left;cell-padding:0; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<h1 class="logo"><a href="https://github.com/fernandoval/Springy"><img src="data:image/png;base64,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" title=""> <span>Springy</span></a></h1>';
        echo '<p>Release <span class="version">' . Kernel::VERSION . '</span></strong>.<br /></p>';

        echo '<p>A micro framework for smart <a href="http://php.net">PHP</a> developers.</p>';

        echo '<p class="description">KISS is our philosophy. KISS is good. KISS is a principle. So write codes with KISS in your mind and <a href="https://en.wikipedia.org/wiki/KISS_principle">keep it simple, silly</a>.</p>';

        echo '<p><strong>List of the library classes:</strong></p><table align="center">';
        $dir = rtrim(dirname(__FILE__), DS) . DS . '..' . DS;
        foreach ($this->listClasses($dir, 'Springy') as $info) {
            echo '<tr><td>' . str_replace('\\', '<span class="slash">\\</span>', str_replace('Springy\\', '<span class="fw">Springy</span>\\', str_replace('class ', '<span class="class">class</span> ', str_replace('interface ', '<span class="class">interface</span> ', $info['n'])))) . '</td><td class="version">' . $info['v'] . ($info['b'] ? '</td><td class="description">' . $info['b'] : '') . '</td></tr>';
        }
        echo '</table>';

        echo '<p><strong>This framework was created by</strong></p><p>';
        echo 'Fernando Val - fernando at fval dot com dot br<br>';
        echo 'Lucas Cardozo - lucas dot cardozo at live dot com<br>';
        echo 'Allan Marques - allan dot marques at ymail dot com</p>';

        echo '<p class="description">This framework is Open Source and distributed under <a href="https://opensource.org/licenses/MIT">MIT</a> license.</p>';

        echo '</p><p><a href="http://fval.com.br"><img src="data:image/png;base64,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" title="Powered by FVAL"></a></p>';

        echo '</body>';
        echo '</html>';
    }
}
