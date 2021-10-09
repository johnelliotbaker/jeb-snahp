<?php
namespace jeb\snahp\Apps\Userscript;

class UserscriptHelper
{
    public function __construct($db, $cache, $tbl, $sauth)
    {
        $this->db = $db;
        $this->cache = $cache;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->userId = $sauth->userId;
    }

    public function getScriptText($uuid)
    {
        return $this->cache->get($uuid);
    }

    public function parseUserscript($message)
    {
        $ptn = "/##start_userscript##(.*?)##end_userscript##/is";
        return preg_replace_callback(
            $ptn,
            function ($matches) {
                $message = trim(strip_tags($matches[1]));
                $name = "";
                if (preg_match("#@name\s+(.*)#", $message, $name)) {
                    $name = trim($name[1]);
                }
                $code = htmlspecialchars_decode($message);
                $hash = md5($code);
                $this->cache->put($hash, $code, 3600);
                return "
<div class='twbs'>
  <div class='d-sm-flex'>
    <div class='ml-auto'>
      <div class='btn-group btn-group-sm text-white' role='group'>
        <a type='button' class='btn btn-sm btn-primary shadow-none userscript__button' href='/snahp/userscript/$hash.user.js'>
          Install ${name}
        </a>
        <div class='btn-group btn-group-sm' role='group'>
          <button id='btnGroupDrop1' type='button' class='btn btn-sm btn-primary shadow-none dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
          </button>
          <div class='dropdown-menu' aria-labelledby='btnGroupDrop1'>
            <a class='dropdown-item' href='https://www.tampermonkey.net/?browser=chrome' target='_blank'>Tampermonkey for Chrome</a>
            <a class='dropdown-item' href='https://www.tampermonkey.net/?browser=firefox' target='_blank'>Tampermonkey for Firefox</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class='codebox'>
  <p>Code: <a href='#' onclick='selectCode(this); return false;'>Select all</a></p>
  <pre><code>$message</code></pre>
</div>
";
            },
            $message
        );
    }
}
