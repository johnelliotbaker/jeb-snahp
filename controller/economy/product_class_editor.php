<?php
namespace jeb\snahp\controller\economy;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class product_class_editor
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $sauth;
    protected $product_class;
    protected $tbl;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $tbl,
        $sauth,
        $product_class
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->product_class = $product_class;
        $this->user_id = (int) $this->user->data["user_id"];
        $sauth->reject_non_dev("f3a997395f");
    }

    public function handle($mode)
    {
        switch ($mode) {
            case "edit":
                $cfg["tpl_name"] =
                    "@jeb_snahp/economy/mcp/product_class_editor/base.html";
                return $this->handle_edit($cfg);
            case "save":
                return $this->handle_save();
            default:
                break;
        }
        trigger_error("Nothing to see here. Move along.");
    }

    private function handle_save()
    {
        $json = (string) htmlspecialchars_decode(
            $this->request->variable("json", "")
        );
        $data = json_decode($json);
        $tmp = [];
        foreach ($data as $k => $d) {
            $tmp[$k] = $d;
        }
        $data = $tmp;
        $b_success = $this->product_class->update_product_class(
            $data["id"],
            $data
        );
        if ($b_success) {
            $rdata = ["status" => "success"];
        } else {
            $rdata = ["status" => "error"];
        }
        $js = new \phpbb\json_response();
        $js->send($rdata);
    }

    private function handle_edit($cfg)
    {
        $rowset = $this->product_class->get_product_classes();
        foreach ($rowset as $row) {
            $row["json"] = json_encode($row);
            $this->template->assign_block_vars("PC", $row);
        }
        return $this->helper->render($cfg["tpl_name"], "Product Class Editor");
    }
}
