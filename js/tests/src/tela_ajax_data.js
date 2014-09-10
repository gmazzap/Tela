var TelaAjaxData = {
    url: 'http://example.com/admin-ajax.php?bid=1&action=telaajax_proxy&telaajax=1',
    url_args: 'bid=1&action=telaajax_proxy&telaajax=1',
    is_admin: '0'
};

var TelaAjaxWideData = {nonces: {}, entrypoints: {test: 'http://example.it/admin-ajax.php?c=1'}};
TelaAjaxWideData.nonces['test::get_text'] = 'get_text_nonce';
TelaAjaxWideData.nonces['test::get_json'] = 'get_json_jnonce';
TelaAjaxWideData.nonces['test::get_html'] = 'get_html_nonce';

var moked_ajax_url = TelaAjaxWideData.entrypoints.test + '&' + TelaAjaxData.url_args;

jQuery.ajax.fake.registerWebservice(moked_ajax_url, function(data) {
    if (!jQuery.type(data) === 'object' || !jQuery.type(data.telaajax_action) === 'string') {
        return false;
    }
    if (!jQuery.type(data.telaajax_data) === 'object') {
        data.telaajax_data = {};
    }
    switch (data.telaajax_action) {
        case 'test::get_text' :
            return 'moked text request';
        case 'test::get_html' :
            return '<p>moked html request</p>';
        case 'test::get_json' :
            return data.telaajax_data;
        default :
            return false;
    }
});