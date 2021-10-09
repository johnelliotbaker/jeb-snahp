<?php

function prn($var, $b_html = false, $depth = 0)
{
    $nl = $b_html ? "<br>" : PHP_EOL;
    $nbsp = $b_html ? "&nbsp;" : " ";
    $tab = $nbsp . $nbsp . $nbsp . $nbsp;
    if ($depth === 0) {
        $stack = debug_backtrace();
        array_shift($stack);
        foreach ($stack as $call) {
            echo $nl . $tab . $call["class"] . "->" . $call["function"];
        }
        echo $nl .
            $tab .
            "----------------------------------------------------------------------------------------------------";
    }
    $indent = [];
    for ($i = 0; $i < $depth; $i++) {
        $indent[] = "...";
    }
    $indent = join("", $indent);
    if (is_array($var)) {
        foreach ($var as $k => $v) {
            echo $nl;
            echo "$indent$k => ";
            prn($v, $b_html, $depth + 1);
        }
    } else {
        if ($b_html) {
            echo htmlspecialchars($var);
        } else {
            echo $var;
        }
    }
    if ($depth === 0) {
        echo $nl;
    }
}

function getDefault($dict, $key, $defualt = null)
{
    if (!array_key_exists($key, $dict)) {
        return $defualt;
    }
    return $dict[$key];
}

function uuid4()
{
    // https://www.php.net/manual/en/function.uniqid.php
    return sprintf(
        "%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,
        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

function getStyleName()
{
    global $db, $user;
    $sql =
        "SELECT style_name FROM " .
        STYLES_TABLE .
        '
        WHERE style_id=' .
        (int) $user->data["user_style"];
    $result = $db->sql_query($sql, 3600);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    switch ($row["style_name"]) {
        case "Hexagon":
            return [$row["style_name"], "hexagon"];
        case "Acieeed!":
            return [$row["style_name"], "acieeed!"];
        case "prosilver":
            return [$row["style_name"], "prosilver"];
        case "Basic":
            return [$row["style_name"], "basic"];
        case "Digi Orange":
        default:
            return [$row["style_name"], "digi_orange"];
    }
}

function getRequestMethod($request)
{
    return strtoupper($request->server("REQUEST_METHOD", "GET"));
}

function getRequestData($request)
{
    $method = getRequestMethod($request);
    if (in_array($method, ["PUT", "PATCH"])) {
        $params = file_get_contents("php://input");
        return json_decode($params, true);
    }
    $data = [];
    foreach ($request->variable_names() as $varname) {
        $data[$varname] = htmlspecialchars_decode(
            $request->variable($varname, "", true)
        );
    }
    return $data;
}

function getRequestUrl($request)
{
    $request->enable_super_globals();
    $url = $_SERVER["REQUEST_URI"];
    $request->disable_super_globals();
    return $url;
}

function getRequestFormData($rootName)
{
    global $request;
    $request->enable_super_globals();
    $data = getDefault($_REQUEST, $rootName);
    $request->disable_super_globals();
    $res = [];
    $collect = function ($arr, &$res, $names = []) use (&$collect) {
        foreach ($arr as $key => $value) {
            $newNames = array_merge($names, [$key]);
            if (!is_array($value)) {
                $res[implode("__", $newNames)] = $value;
            } else {
                $collect($value, $res, $newNames);
            }
        }
    };
    $collect($data, $res);
    return $res;
}

function getTwigRenderer($templateDirs = [], $extensions = [])
{
    $templateDir = "/var/www/forum/ext/jeb/snahp/styles/all/template";
    $defaultTemplateDir = [""];
    $twig = new \Twig\Environment(
        new \Twig\Loader\FilesystemLoader($templateDirs)
    );
    // $defaultTheme = 'form_div_layout.html.twig';
    // $formEngine = new \Symfony\Bridge\Twig\Form\TwigRendererEngine([$defaultTheme], $twig);
    $formEngine = new \Symfony\Bridge\Twig\Form\TwigRendererEngine([], $twig);
    $twig->addRuntimeLoader(
        new \Twig\RuntimeLoader\FactoryRuntimeLoader([
            \Symfony\Component\Form\FormRenderer::class => function () use (
                $formEngine,
                $csrfManager
            ) {
                return new \Symfony\Component\Form\FormRenderer(
                    $formEngine,
                    $csrfManager
                );
            },
        ])
    );
    foreach ($extensions as $extension) {
        $twig->addExtension($extension);
    }
    return $twig;
}

function flattenArray($arr)
{
    if (!is_array($arr)) {
        return $arr;
    }
    $res = [];
    $flatten = function ($arr, &$res) {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                flatten($value, $res);
            } else {
                $res[$key] = $value;
            }
        }
    };
    $flatten($arr, $res);
    return $res;
}

function asSet($array)
{
    if (is_array($array)) {
        return array_flip($array);
    }
}

function choice($array)
{
    return $array[array_rand($array, 1)];
}

/*************************************************************************
 *                            LODASH IMPORTS                             *
 *************************************************************************/
const asciiWords = '/[^\x00-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f]+/';

function kebabCase(string $string)
{
    return implode(
        "-",
        array_map(
            "strtolower",
            words(preg_replace("/['\x{2019}]/u", "", $string))
        )
    );
}

function words(string $string, string $pattern = null): array
{
    if (null === $pattern) {
        preg_match_all(asciiWords, $string, $matches);
        return $matches[0] ?? [];
    }
    if (preg_match_all($pattern, $string, $matches) > 0) {
        return $matches[0];
    }
    return [];
}
