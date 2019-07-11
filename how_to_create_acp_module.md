### migration/
#### Add update_data() entry
```
public function update_data()
{
    return [
        ['module.add', [
            'acp',
        'ACP_SNP_TITLE',
        [
            'module_basename' => '\jeb\snahp\acp\main_module',
            'modes'           => ['emotes'],
        ]
        ]],
        ['config.add', ['snp_emo_b_master', 1]],
    ];
}
```

### acp/
#### main_info.php
- Add entry into the 'modes'
```
'emotes' => array(
        'title' => 'ACP_SNP_EMOTES',
        'auth'  => 'ext_jeb/snahp && acl_a_board',
        'cat'   => array('ACP_SNP_TITLE')
        ),
```
### language/
#### en/info_acp_demo.php
- Add title from main_info.php
```
'ACP_SNP_EMOTES' => 'Emoticons',
```

### acp/
#### main_module.php
- add handler in the main_module.php
In the handler function set
`$this->tpl_name = $tpl_name;`
So the template handler can correctly find the template.


### adm/styles/
#### [module_name].html
- add style template to be loaded
The name should be same as $tpl_name from main_module.php

