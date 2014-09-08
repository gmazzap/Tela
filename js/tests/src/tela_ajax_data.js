var TelaAjaxData = {
    url: 'http://example.com/admin-ajax.php?bid=1&action=telaajax_proxy&telaajax=1',
    is_admin: '0'
};

var TelaAjaxNonces = {nonces: {}};
TelaAjaxNonces.nonces['test::get_text'] = 'get_text_nonce';
TelaAjaxNonces.nonces['test::get_json'] = 'get_json_jnonce';
TelaAjaxNonces.nonces['test::get_html'] = 'get_html_nonce';

jQuery.ajax.fake.registerWebservice(TelaAjaxData.url, function(data) {
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

