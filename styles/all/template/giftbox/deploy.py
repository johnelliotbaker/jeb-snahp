import os
import re
import glob

giftbox_path = '/var/www/forum/ext/jeb/snahp/styles/all/template/giftbox'
dist_path = os.path.join(giftbox_path, 'dist')
static_path = os.path.join(dist_path, 'static')

def read_source():
    #  with open('/www/var/forum/ext//dist/index.html', 'r') as f:
    with open(os.path.join(giftbox_path, 'dist', 'index.html'), 'r') as f:
        return f.read()

def collect_links(data):
    matches = re.findall(r'<link.*?>', data);
    return matches

def collect_scripts(data):
    matches = re.findall(r'<script.*?</script>', data);
    return matches

def create_base(data):
    script_tags = collect_scripts(data)
    print(script_tags)
    link_tags = collect_links(data)
    html = []
    html += link_tags + ['<div id="christmas-giveaway"></div>'] + script_tags
    return ''.join(html)

def replace_static_path(html):
    base_dir = '/ext/jeb/snahp/styles/all/template/giftbox/dist'
    return re.sub(r'/static', os.path.join(base_dir, 'static'), html)


def create_index():
    data = read_source()
    html = create_base(data)
    html = replace_static_path(html)
    return html

def set_font_path():
    css_path = os.path.join(static_path, 'css')
    filenames = glob.glob(os.path.join(css_path, '*.css'))
    dist_url = '/ext/jeb/snahp/styles/all/template/giftbox/dist/';
    for filename in filenames:
        with open(filename, 'r', encoding='utf-8') as f:
            data = f.read()
            data = re.sub('url\(\/(.*?)\)', 'url(' + dist_url +  r"\1)", data)
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(data)


def main():
    html = create_index()
    with open(os.path.join(giftbox_path, 'index.html'), 'w', encoding='utf-8') as f:
        f.write(html)
    set_font_path()


if __name__ == "__main__":
    main()
