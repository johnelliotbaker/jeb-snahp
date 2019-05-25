<?php
namespace jeb\snahp\mcp;
class main_info
{
    public function module()
    {
        return [
            'filename' => '\jeb\snahp\mcp\main_module',
            'title'    => 'MCP_SNP_TITLE',
            'modes'    => [
                'request'    => [
                    'title'  => 'MCP_SNP_REQUEST',
                    'auth'   => 'ext_jeb/snahp',
                    'cat'    => ['MCP_SNP_TITLE']
                ],
                'dibs'       => [
                    'title'  => 'MCP_SNP_DIBS',
                    'auth'   => 'ext_jeb/snahp',
                    'cat'    => ['MCP_SNP_TITLE']
                ],
                'ban'        => [
                    'title'  => 'MCP_SNP_BAN',
                    'auth'   => 'ext_jeb/snahp',
                    'cat'    => ['MCP_SNP_TITLE']
                ],
                'topic_bump' => [
                    'title'  => 'MCP_SNP_TOPIC_BUMP',
                    'auth'   => 'ext_jeb/snahp',
                    'cat'    => ['MCP_SNP_TITLE']
                ],
                'invite' => [
                    'title'  => 'MCP_SNP_INVITE',
                    'auth'   => 'ext_jeb/snahp',
                    'cat'    => ['MCP_SNP_TITLE']
                ],
                'scripts'    => [
                    'title'  => 'MCP_SNP_SCRIPTS',
                    'auth'   => 'ext_jeb/snahp',
                    'cat'    => ['MCP_SNP_TITLE']
                ],
            ],
        ];
    }
}
