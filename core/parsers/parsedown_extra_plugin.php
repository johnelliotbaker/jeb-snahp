<?php

#
#
# Parsedown Extra Plugin
# https://github.com/tovic/parsedown-extra-plugin
#
# (c) Emanuil Rusev
# http://erusev.com
#
# (c) Taufik Nurrohman
# https://mecha-cms.com
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#

namespace jeb\snahp\core\parsers;

use jeb\snahp\core\parsers\parsedown_extra;

class parsedown_extra_plugin extends parsedown_extra
{
    const version = "1.2.0-beta-3";

    # config

    public $abbreviationData = [];

    public $blockCodeAttributes = [];

    public $blockCodeAttributesOnParent = false;

    public $blockCodeClassFormat = "language-%s";

    public $blockCodeHtml = null;

    public $blockQuoteAttributes = [];

    public $blockQuoteText = null;

    public $codeAttributes = [];

    public $codeHtml = null;

    public $footnoteAttributes = [];

    public $footnoteBackLinkAttributes = [];

    public $footnoteBackLinkHtml = null;

    public $footnoteBackReferenceAttributes = [];

    public $footnoteLinkAttributes = [];

    public $footnoteLinkHtml = null;

    public $footnoteReferenceAttributes = [];

    public $headerAttributes = [];

    public $headerText = null;

    public $imageAttributes = [];

    public $linkAttributes = [];

    public $referenceData = [];

    public $tableAttributes = [];

    public $tableColumnAttributes = [];

    public $voidElementSuffix = " />";

    # config

    protected $regexAttribute = '(?:[#.][-\w:\\\]+[ ]*|[-\w:\\\]+(?:=(?:["\'][^\n]*?["\']|[^\s]+)?)?[ ]*)';

    # Method aliases for every configuration property
    public function __call($key, array $arguments = [])
    {
        $property = lcfirst(substr($key, 3));
        if (strpos($key, "set") === 0 && property_exists($this, $property)) {
            $this->{$property} = $arguments[0];
            return $this;
        }
        throw new Exception("Method " . $key . " does not exists.");
    }

    public function __construct()
    {
        if (version_compare(parent::version, "0.8.0-beta-1") < 0) {
            throw new Exception(
                "ParsedownExtraPlugin requires a later version of Parsedown"
            );
        }
        parent::__construct();
    }

    protected function blockAbbreviation($Line)
    {
        # Allow empty abbreviations
        if (preg_match('/^\*\[(.+?)\]:[ ]*$/', $Line["text"], $matches)) {
            $this->DefinitionData["Abbreviation"][$matches[1]] = null;
            return ["hidden" => true];
        }
        $this->doSetData(
            $this->DefinitionData["Abbreviation"],
            $this->abbreviationData
        );
        return parent::blockAbbreviation($Line);
    }

    protected function blockCodeComplete($Block)
    {
        $this->doSetAttributes(
            $Block["element"]["element"],
            $this->blockCodeAttributes
        );
        $this->doSetContent(
            $Block["element"]["element"],
            $this->blockCodeHtml,
            true
        );
        # Put code attributes on parent tag
        if ($this->blockCodeAttributesOnParent) {
            $Block["element"]["attributes"] =
                $Block["element"]["element"]["attributes"];
            unset($Block["element"]["element"]["attributes"]);
        }
        $Block["element"]["element"]["rawHtml"] =
            $Block["element"]["element"]["text"];
        $Block["element"]["element"]["allowRawHtmlInSafeMode"] = true;
        unset($Block["element"]["element"]["text"]);
        return $Block;
    }

    protected function blockFencedCode($Line)
    {
        # Re-enable the multiple class name feature
        $Line["text"] = strtr(trim($Line["text"]), [
            " " => "\x1A",
            "." => "\x1A.",
        ]);
        # Enable custom attribute syntax on code block
        $Attributes = [];
        if (
            strpos($Line["text"], "{") !== false &&
            substr($Line["text"], -1) === "}"
        ) {
            $Parts = explode("{", $Line["text"], 2);
            $Attributes = $this->parseAttributeData(
                strtr(substr($Parts[1], 0, -1), "\x1A", " ")
            );
            $Line["text"] = trim($Parts[0]);
        }
        if (!($Block = parent::blockFencedCode($Line))) {
            return;
        }
        if ($Attributes) {
            $Block["element"]["element"]["attributes"] = $Attributes;
        } elseif (isset($Block["element"]["element"]["attributes"]["class"])) {
            $Classes = explode(
                "\x1A",
                strtr(
                    $Block["element"]["element"]["attributes"]["class"],
                    " ",
                    "\x1A"
                )
            );
            // `~~~ php` → `<pre><code class="language-php">`
            // `~~~ php html` → `<pre><code class="language-php language-html">`
            // `~~~ .php` → `<pre><code class="php">`
            // `~~~ .php.html` → `<pre><code class="php html">`
            // `~~~ .php html` → `<pre><code class="php language-html">`
            // `~~~ {.php #foo}` → `<pre><code id="foo" class="php">`
            $Results = [];
            foreach ($Classes as $Class) {
                if (
                    $Class === "" ||
                    $Class ===
                        str_replace("%s", "", $this->blockCodeClassFormat)
                ) {
                    continue;
                }
                if ($Class[0] === ".") {
                    $Results[] = substr($Class, 1);
                } else {
                    $Results[] = sprintf($this->blockCodeClassFormat, $Class);
                }
            }
            $Block["element"]["element"]["attributes"]["class"] = implode(
                " ",
                array_unique($Results)
            );
        }
        return $Block;
    }

    protected function blockFencedCodeComplete($Block)
    {
        return $this->blockCodeComplete($Block);
    }

    protected function blockHeader($Line)
    {
        if (!($Block = parent::blockHeader($Line))) {
            return;
        }
        $Level = strspn($Line["text"], "#");
        $this->doSetAttributes($Block["element"], $this->headerAttributes, [
            $Level,
        ]);
        $this->doSetContent(
            $Block["element"],
            $this->headerText,
            false,
            "argument",
            [$Level]
        );
        return $Block;
    }

    protected function blockQuoteComplete($Block)
    {
        $this->doSetAttributes($Block["element"], $this->blockQuoteAttributes);
        $this->doSetContent(
            $Block["element"],
            $this->blockQuoteText,
            false,
            "arguments"
        );
        return $Block;
    }

    protected function blockSetextHeader($Line, array $Block = null)
    {
        if (!($Block = parent::blockSetextHeader($Line, $Block))) {
            return;
        }
        $Level = $Line["text"][0] === "=" ? 1 : 2;
        $this->doSetAttributes($Block["element"], $this->headerAttributes, [
            $Level,
        ]);
        $this->doSetContent(
            $Block["element"],
            $this->headerText,
            false,
            "argument",
            [$Level]
        );
        return $Block;
    }

    protected function blockTableContinue($Line, array $Block)
    {
        if (!($Block = parent::blockTableContinue($Line, $Block))) {
            return;
        }
        $Aligns = $Block["alignments"];
        // `<thead>` or `<tbody>`
        foreach ($Block["element"]["elements"] as $Index0 => &$Element0) {
            // `<tr>`
            foreach ($Element0["elements"] as $Index1 => &$Element1) {
                // `<th>` or `<td>`
                foreach ($Element1["elements"] as $Index2 => &$Element2) {
                    $this->doSetAttributes(
                        $Element2,
                        $this->tableColumnAttributes,
                        [$Aligns[$Index2], $Index2, $Index1]
                    );
                }
            }
        }
        return $Block;
    }

    protected function blockTableComplete($Block)
    {
        $this->doSetAttributes($Block["element"], $this->tableAttributes);
        return $Block;
    }

    protected function buildFootnoteElement()
    {
        $DefinitionData = $this->DefinitionData["Footnote"];
        if (!($Footnotes = parent::buildFootnoteElement())) {
            return;
        }
        $DefinitionKey = array_keys($DefinitionData);
        $DefinitionData = array_values($DefinitionData);
        $this->doSetAttributes($Footnotes, $this->footnoteAttributes);
        foreach (
            $Footnotes["elements"][1]["elements"]
            as $Index0 => &$Element0
        ) {
            $Name = $DefinitionKey[$Index0];
            $Count = $DefinitionData[$Index0]["count"];
            $Args = [is_numeric($Name) ? (float) $Name : $Name, $Count];
            $this->doSetAttributes(
                $Element0,
                $this->footnoteBackReferenceAttributes,
                $Args
            );
            foreach ($Element0["elements"] as $Index1 => &$Element1) {
                $Count = 0;
                foreach ($Element1["elements"] as $Index2 => &$Element2) {
                    if (
                        !isset($Element2["name"]) ||
                        $Element2["name"] !== "a"
                    ) {
                        continue;
                    }
                    $Args[1] = ++$Count;
                    $this->doSetAttributes(
                        $Element2,
                        $this->footnoteBackLinkAttributes,
                        $Args
                    );
                    $this->doSetContent(
                        $Element2,
                        $this->footnoteBackLinkHtml,
                        false,
                        "rawHtml"
                    );
                }
            }
        }
        return $Footnotes;
    }

    protected function doGetAttributes($Element)
    {
        if (isset($Element["attributes"])) {
            return (array) $Element["attributes"];
        }
        return [];
    }

    protected function doGetContent($Element)
    {
        if (isset($Element["text"])) {
            return $Element["text"];
        }
        if (isset($Element["rawHtml"])) {
            return $Element["rawHtml"];
        }
        if (isset($Element["handler"]["argument"])) {
            return implode("\n", (array) $Element["handler"]["argument"]);
        }
        return null;
    }

    private function doSetLink($Excerpt, $Function)
    {
        if (!($Inline = call_user_func("parent::" . $Function, $Excerpt))) {
            return;
        }
        $this->doSetAttributes($Inline["element"], $this->linkAttributes, [
            $this->isLocal($Inline["element"], "href"),
        ]);
        $this->doSetData(
            $this->DefinitionData["Reference"],
            $this->referenceData
        );
        return $Inline;
    }

    protected function doSetAttributes(&$Element, $From, $Args = [])
    {
        $Attributes = $this->doGetAttributes($Element);
        $Content = $this->doGetContent($Element);
        if (is_callable($From)) {
            $Args = array_merge([$Content, $Attributes, &$Element], $Args);
            $Element["attributes"] = array_replace(
                $Attributes,
                (array) call_user_func_array($From, $Args)
            );
        } else {
            $Element["attributes"] = array_replace($Attributes, (array) $From);
        }
    }

    protected function doSetContent(
        &$Element,
        $From,
        $Esc = false,
        $Mode = "text",
        $Args = []
    ) {
        $Attributes = $this->doGetAttributes($Element);
        $Content = $this->doGetContent($Element);
        if ($Esc) {
            $Content = parent::escape($Content, true);
        }
        if (is_callable($From)) {
            $Args = array_merge([$Content, $Attributes, &$Element], $Args);
            $Content = call_user_func_array($From, $Args);
        } elseif (!empty($From)) {
            $Content = sprintf($From, $Content);
        }
        if ($Mode === "arguments") {
            $Element["handler"]["argument"] = explode("\n", $Content);
        } elseif ($Mode === "argument") {
            $Element["handler"]["argument"] = $Content;
        } else {
            $Element[$Mode] = $Content;
        }
    }

    protected function doSetData(&$To, $From)
    {
        $To = array_replace((array) $To, (array) $From);
    }

    protected function element(array $Element)
    {
        if (!($Any = parent::element($Element))) {
            return;
        }
        if (substr($Any, -3) === " />") {
            if (is_callable($this->voidElementSuffix)) {
                $Attributes = $this->doGetAttributes($Element);
                $Content = $this->doGetContent($Element);
                $Suffix = call_user_func_array($this->voidElementSuffix, [
                    $Content,
                    $Attributes,
                    &$Element,
                ]);
            } else {
                $Suffix = $this->voidElementSuffix;
            }
            $Any = substr_replace($Any, $Suffix, -3);
        }
        return $Any;
    }

    protected function inlineCode($Excerpt)
    {
        if (!($Inline = parent::inlineCode($Excerpt))) {
            return;
        }
        $this->doSetAttributes($Inline["element"], $this->codeAttributes);
        $this->doSetContent($Inline["element"], $this->codeHtml, true);
        $Inline["element"]["rawHtml"] = $Inline["element"]["text"];
        $Inline["element"]["allowRawHtmlInSafeMode"] = true;
        unset($Inline["element"]["text"]);
        return $Inline;
    }

    protected function inlineFootnoteMarker($Excerpt)
    {
        if (!($Inline = parent::inlineFootnoteMarker($Excerpt))) {
            return;
        }
        $Name = null;
        if (preg_match("/^\[\^(.+?)\]/", $Excerpt["text"], $matches)) {
            $Name = $matches[1];
        }
        $Args = [
            is_numeric($Name) ? (float) $Name : $Name,
            $this->DefinitionData["Footnote"][$Name]["count"],
        ];
        $this->doSetAttributes(
            $Inline["element"],
            $this->footnoteReferenceAttributes,
            $Args
        );
        $this->doSetAttributes(
            $Inline["element"]["element"],
            $this->footnoteLinkAttributes,
            $Args
        );
        $this->doSetContent(
            $Inline["element"]["element"],
            $this->footnoteLinkHtml,
            false,
            "text",
            $Args
        );
        $Inline["element"]["element"]["rawHtml"] =
            $Inline["element"]["element"]["text"];
        $Inline["element"]["element"]["allowRawHtmlInSafeMode"] = true;
        unset($Inline["element"]["element"]["text"]);
        return $Inline;
    }

    protected function inlineImage($Excerpt)
    {
        if (!($Inline = parent::inlineImage($Excerpt))) {
            return;
        }
        $this->doSetAttributes($Inline["element"], $this->imageAttributes, [
            $this->isLocal($Inline["element"], "src"),
        ]);
        return $Inline;
    }

    protected function inlineLink($Excerpt)
    {
        return $this->doSetLink($Excerpt, __FUNCTION__);
    }

    protected function inlineUrl($Excerpt)
    {
        return $this->doSetLink($Excerpt, __FUNCTION__);
    }

    protected function inlineUrlTag($Excerpt)
    {
        return $this->doSetLink($Excerpt, __FUNCTION__);
    }

    protected function isLocal($Element, $Key)
    {
        $Link = isset($Element["attributes"][$Key])
            ? (string) $Element["attributes"][$Key]
            : null;
        if (
            // `<a href="">`
            $Link === "" ||
            // `<a href="../foo/bar">`
            // `<a href="/foo/bar">`
            // `<a href="?foo=bar">`
            // `<a href="&foo=bar">`
            // `<a href="#foo">`
            (strpos("./?&#", $Link[0]) !== false &&
                strpos($Link, "//") !== 0) ||
            // `<a href="data:text/html,asdf">`
            strpos($Link, "data:") === 0 ||
            // `<a href="javascript:;">`
            strpos($Link, "javascript:") === 0
        ) {
            return true;
        }
        $Host = "";
        // `<a href="//example.com">`
        if (strpos($Link, "//") === 0 && strpos($Link, "//" . $Host) !== 0) {
            return false;
        }
        if (
            // `<a href="https://127.0.0.1">`
            strpos($Link, "https://" . $Host) === 0 ||
            // `<a href="http://127.0.0.1">`
            strpos($Link, "http://" . $Host) === 0
        ) {
            return true;
        }
        // `<a href="foo/bar">`
        return strpos($Link, "://") === false;
    }

    protected function parseAttributeData($attributeString)
    {
        # Allow compact attributes
        $attributeString = strtr($attributeString, [
            "#" => " #",
            "." => " .",
        ]);
        if (
            strpos($attributeString, '="') !== false ||
            strpos($attributeString, "='") !== false
        ) {
            $attributeString = preg_replace_callback(
                '#([-\w]+=)(["\'])([^\n]*?)\2#',
                function ($matches) {
                    $value = strtr($matches[3], [
                        " #" => "#",
                        " ." => ".",
                        " " => "\x1A",
                    ]);
                    return $matches[1] . $matches[2] . $value . $matches[2];
                },
                $attributeString
            );
        }
        $Attributes = [];
        foreach (explode(" ", $attributeString) as $v) {
            if (!$v) {
                continue;
            }
            // `{#foo}`
            if ($v[0] === "#" && isset($v[1])) {
                $Attributes["id"] = substr($v, 1);
                // `{.foo}`
            } elseif ($v[0] === "." && isset($v[1])) {
                $Attributes["class"][] = substr($v, 1);
                // ~
            } elseif (strpos($v, "=") !== false) {
                $vv = explode("=", $v, 2);
                // `{foo=}`
                if ($vv[1] === "") {
                    $Attributes[$vv[0]] = "";
                    // `{foo="bar baz"}`
                    // `{foo='bar baz'}`
                } elseif (
                    ($vv[1][0] === '"' && substr($vv[1], -1) === '"') ||
                    ($vv[1][0] === "'" && substr($vv[1], -1) === "'")
                ) {
                    $Attributes[$vv[0]] = stripslashes(
                        strtr(substr(substr($vv[1], 1), 0, -1), "\x1A", " ")
                    );
                    // `{foo=bar}`
                } else {
                    $Attributes[$vv[0]] = $vv[1];
                }
                // `{foo}`
            } else {
                $Attributes[$v] = $v;
            }
        }
        if (isset($Attributes["class"])) {
            $Attributes["class"] = implode(
                " ",
                array_unique($Attributes["class"])
            );
        }
        return $Attributes;
    }
}
